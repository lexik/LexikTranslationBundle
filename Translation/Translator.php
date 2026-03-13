<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Finder\Finder;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as SymfonyTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

/**
 * Translator service class that decorates Symfony's Translator.
 *
 * Uses composition instead of inheritance to be compatible with Symfony 8
 * where the Translator class is final.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translator implements TranslatorInterface, LocaleAwareInterface, TranslatorBagInterface
{
    private SymfonyTranslator $translator;
    protected array $resourceLocales = [];
    protected array $resources = [];
    protected array $resourceFiles = [];
    protected array $scannedDirectories = [];
    protected string $cacheFile;
    private bool $isResourcesLoaded = false;

    /** @var array For tracking database resources (mainly for testing) */
    public array $dbResources = [];

    public function __construct(
        protected ContainerInterface $container,
        MessageFormatter $formatter,
        string $defaultLocale,
        protected array $loaderIds,
        protected array $options
    ) {
        $this->resourceLocales = [];
        $this->resources = [];
        $this->resourceFiles = [];
        $this->scannedDirectories = [];

        $this->options['resource_files'] = $this->options['resource_files'] ?? [];
        $this->options['scanned_directories'] = $this->options['scanned_directories'] ?? [];
        $this->options['cache_vary'] = $this->options['cache_vary'] ?? [];
        $this->options['cache_dir'] = $this->options['cache_dir'] ?? sys_get_temp_dir();
        $this->options['debug'] = $this->options['debug'] ?? false;
        $this->options['resources_type'] = $this->options['resources_type'] ?? 'all';

        $this->cacheFile = sprintf('%s/database.resources.php', $this->options['cache_dir']);

        // Filter out custom options that Symfony translator doesn't recognize
        // Only pass valid Symfony translator options
        $symfonyOptions = [
            'cache_dir' => $this->options['cache_dir'],
            'debug' => $this->options['debug'],
        ];

        // Add other valid Symfony options if they exist
        $validSymfonyOptions = ['cache_dir', 'debug', 'resource_files', 'scanned_directories', 'cache_vary'];
        foreach ($validSymfonyOptions as $key) {
            if (isset($this->options[$key])) {
                $symfonyOptions[$key] = $this->options[$key];
            }
        }

        // Create the inner Symfony translator
        $this->translator = new SymfonyTranslator(
            container: $this->container,
            formatter: $formatter,
            defaultLocale: $defaultLocale,
            loaderIds: $this->loaderIds,
            options: $symfonyOptions,
            enabledLocales: []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $this->loadDatabaseResourcesIfNeeded();
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogue(?string $locale = null): \Symfony\Component\Translation\MessageCatalogueInterface
    {
        $this->loadDatabaseResourcesIfNeeded();
        return $this->translator->getCatalogue($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogues(): array
    {
        return $this->translator->getCatalogues();
    }

    /**
     * Load database resources if needed.
     */
    private function loadDatabaseResourcesIfNeeded(): void
    {
        $resourcesType = $this->options['resources_type'] ?? 'all';

        if (!$this->isResourcesLoaded && ('all' === $resourcesType || 'database' === $resourcesType)) {
            $this->addDatabaseResources();
        }
    }

    /**
     * Add all resources available in database.
     */
    public function addDatabaseResources(): void
    {
        $cache = new ConfigCache($this->cacheFile, $this->options['debug'] ?? false);

        if (!$cache->isFresh()) {
            $event = new GetDatabaseResourcesEvent();
            $this->container->get('event_dispatcher')->dispatch($event);

            $resources = $event->getResources();
            $metadata = [];

            foreach ($resources as $resource) {
                $metadata[] = new DatabaseFreshResource($resource['locale'], $resource['domain'] ?? 'messages');
            }

            $content = sprintf("<?php return %s;", var_export($resources, true));
            $cache->write($content, $metadata);
        } else {
            $resources = include $this->cacheFile;
        }

        // Use reflection to access the addResource method on the inner translator
        $reflection = new \ReflectionClass($this->translator);
        $addResourceMethod = $reflection->getMethod('addResource');

        foreach ($resources as $resource) {
            $locale = $resource['locale'];
            $domain = $resource['domain'] ?? 'messages';
            $addResourceMethod->invoke($this->translator, 'database', 'DB', $locale, $domain);

            // Track for testing purposes
            $this->dbResources[$locale][] = ['database', 'DB', $domain];
        }

        $this->isResourcesLoaded = true;
    }

    /**
     * Remove the cache file corresponding to the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function removeCacheFile($locale)
    {
        if (!file_exists($this->cacheFile)) {
            return true;
        }

        $localeExploded = explode('_', $locale);
        $finder = new Finder();
        $finder->files()->in($this->options['cache_dir'])->name(sprintf( '/catalogue\.%s.*\.php$/', $localeExploded[0]));
        $deleted = true;
        foreach ($finder as $file) {

            $path = $file->getRealPath();
            $this->invalidateSystemCacheForFile($path);
            $deleted = unlink($path);

            $metadata = $path.'.meta';
            if (file_exists($metadata)) {
                $this->invalidateSystemCacheForFile($metadata);
                unlink($metadata);
            }
        }

        return $deleted;
    }

    /**
     * Remove the cache file corresponding to each given locale.
     */
    public function removeLocalesCacheFiles(array $locales)
    {
        foreach ($locales as $locale) {
            $this->removeCacheFile($locale);
        }

        // also remove database.resources.php cache file
        $file = sprintf('%s/database.resources.php', $this->options['cache_dir']);
        if (file_exists($file)) {
            $this->invalidateSystemCacheForFile($file);
            unlink($file);
        }

        $metadata = $file.'.meta';
        if (file_exists($metadata)) {
            $this->invalidateSystemCacheForFile($metadata);
            unlink($metadata);
        }

        $this->isResourcesLoaded = false;
    }

    /**
     * @param string $path
     *
     * @throws \RuntimeException
     */
    protected function invalidateSystemCacheForFile($path)
    {
        if (ini_get('apc.enabled') && function_exists('apc_delete_file')) {
            if (apc_exists($path) && !apc_delete_file($path)) {
                throw new \RuntimeException(sprintf('Failed to clear APC Cache for file %s', $path));
            }
        } elseif ('cli' === php_sapi_name() ? ini_get('opcache.enable_cli') : ini_get('opcache.enable')) {
            if (function_exists("opcache_invalidate") && !opcache_invalidate($path, true)) {
                throw new \RuntimeException(sprintf('Failed to clear OPCache for file %s', $path));
            }
        }
    }

    /**
     * Returns all translations file formats.
     *
     * @return array
     */
    public function getFormats()
    {
        $allFormats = [];

        foreach ($this->loaderIds as $id => $formats) {
            foreach ($formats as $format) {
                if ('database' !== $format) {
                    $allFormats[] = $format;
                }
            }
        }

        return $allFormats;
    }

    /**
     * Returns a loader according to the given format.
     *
     * @param string $format
     * @throws \RuntimeException
     * @return LoaderInterface
     */
    public function getLoader($format)
    {
        $loader = null;
        $i = 0;
        $ids = array_keys($this->loaderIds);

        while ($i < count($ids) && null === $loader) {
            if (in_array($format, $this->loaderIds[$ids[$i]])) {
                $loader = $this->container->get($ids[$i]);
            }
            $i++;
        }

        if (!($loader instanceof LoaderInterface)) {
            throw new \RuntimeException(sprintf('No loader found for "%s" format.', $format));
        }

        return $loader;
    }

    /**
     * Set fallback locales.
     */
    public function setFallbackLocales(array $locales): void
    {
        $this->translator->setFallbackLocales($locales);
    }

    /**
     * Get fallback locales.
     */
    public function getFallbackLocales(): array
    {
        return $this->translator->getFallbackLocales();
    }

    /**
     * Warms up the cache.
     */
    public function warmUp(string $cacheDir): array
    {
        return $this->translator->warmUp($cacheDir);
    }

    /**
     * Set config cache factory.
     */
    public function setConfigCacheFactory(\Symfony\Component\Config\ConfigCacheFactoryInterface $configCacheFactory): void
    {
        $this->translator->setConfigCacheFactory($configCacheFactory);
    }

    /**
     * Add resource to the inner translator.
     */
    public function addResource(string $format, mixed $resource, string $locale, ?string $domain = null): void
    {
        // Use reflection to access the protected addResource method
        $reflection = new \ReflectionClass($this->translator);
        $addResourceMethod = $reflection->getMethod('addResource');
        $addResourceMethod->invoke($this->translator, $format, $resource, $locale, $domain);
    }
}
