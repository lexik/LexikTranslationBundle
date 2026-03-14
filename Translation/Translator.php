<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Decorator for Symfony Translator service to add database translation resources.
 *
 * This decorator wraps the original translator service and adds functionality
 * to load translations from the database. It implements TranslatorInterface
 * to maintain compatibility with Symfony 8 where Translator is final.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translator implements TranslatorInterface
{
    private bool $isResourcesLoaded = false;
    private string $cacheFile;

    public function __construct(
        private $translator,
        private readonly ContainerInterface $container,
        private readonly array $loaderIds,
        private array $options
    ) {
        $this->options['cache_dir'] = $this->options['cache_dir'] ?? sys_get_temp_dir();
        $this->options['debug'] = $this->options['debug'] ?? false;
        $this->options['resources_type'] = $this->options['resources_type'] ?? 'all';
        $this->cacheFile = sprintf('%s/database.resources.php', $this->options['cache_dir']);
    }

    public function setConfigCacheFactory(ConfigCacheFactoryInterface $configCacheFactory): void
    {
        $this->translator->setConfigCacheFactory($configCacheFactory);
    }

    public function addLoader(string $format, LoaderInterface $loader): void
    {
        $this->translator->addLoader($format, $loader);
    }

    public function addResource(string $format, mixed $resource, string $locale, ?string $domain = null): void
    {
        $this->translator->addResource($format, $resource, $locale, $domain);
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setFallbackLocales(array $locales): void
    {
        $this->translator->setFallbackLocales($locales);
    }

    public function getFallbackLocales(): array
    {
        return $this->translator->getFallbackLocales();
    }

    public function addGlobalParameter(string $id, string|int|float|TranslatableInterface $value): void
    {
        $this->translator->addGlobalParameter($id, $value);
    }

    public function getGlobalParameters(): array
    {
        return $this->translator->getGlobalParameters();
    }

    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $resourcesType = $this->options['resources_type'];

        if (!$this->isResourcesLoaded && ('all' === $resourcesType || 'database' === $resourcesType)) {
            $this->addDatabaseResources();
        }

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    public function getCatalogues(): array
    {
        return $this->translator->getCatalogues();
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        return $this->translator->warmUp($cacheDir, $buildDir);
    }

    /**
     * Add all resources available in database.
     * 
     * This method is called by DatabaseResourcesListener to register
     * database translation resources with the translator.
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

        foreach ($resources as $resource) {
            $this->addResource('database', 'DB', $resource['locale'], $resource['domain'] ?? 'messages');
        }

        $this->isResourcesLoaded = true;
    }

    /**
     * Remove the cache file corresponding to the given locale.
     */
    public function removeCacheFile(string $locale): bool
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
    public function removeLocalesCacheFiles(array $locales): void
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
     * @throws \RuntimeException
     */
    protected function invalidateSystemCacheForFile(string $path): void
    {
        if (ini_get('apc.enabled') && function_exists('apc_delete_file')) {
            if (apc_exists($path) && !apc_delete_file($path)) {
                throw new \RuntimeException(sprintf('Failed to clear APC Cache for file %s', $path));
            }
        } elseif ('cli' === PHP_SAPI ? ini_get('opcache.enable_cli') : ini_get('opcache.enable')) {
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
    public function getFormats(): array
    {
        $allFormats = [];

        foreach ($this->loaderIds as $formats) {
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getLoader(string $format): LoaderInterface
    {
        $loader = null;
        $i = 0;
        $ids = array_keys($this->loaderIds);

        while ($i < count($ids) && null === $loader) {
            if (\in_array($format, $this->loaderIds[ $ids[ $i ] ], true)) {
                $loader = $this->container->get($ids[$i]);
            }
            $i++;
        }

        if (!($loader instanceof LoaderInterface)) {
            throw new \RuntimeException(sprintf('No loader found for "%s" format.', $format));
        }

        return $loader;
    }
}
