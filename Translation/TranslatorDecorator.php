<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\LoaderInterface;
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
class TranslatorDecorator implements TranslatorInterface
{
    private bool $isResourcesLoaded = false;
    private string $cacheFile;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly \Psr\Container\ContainerInterface $container,
        private readonly array $options
    ) {
        $this->options['cache_dir'] = $this->options['cache_dir'] ?? sys_get_temp_dir();
        $this->options['debug'] = $this->options['debug'] ?? false;
        $this->options['resources_type'] = $this->options['resources_type'] ?? 'all';
        $this->cacheFile = sprintf('%s/database.resources.php', $this->options['cache_dir']);
    }

    /**
     * {@inheritdoc}
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        // Database resources are loaded via DatabaseResourcesListener event subscriber
        // No need to load here as the loader is registered and will be called automatically
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
     * Add all resources available in database.
     * 
     * This method is called by DatabaseResourcesListener to register
     * database translation resources with the translator.
     */
    public function addDatabaseResources(): void
    {
        if ($this->isResourcesLoaded) {
            return;
        }

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

        // Add resources using reflection to access protected addResource method
        // or use the public API if available in Symfony 8
        $reflection = new \ReflectionClass($this->translator);
        if ($reflection->hasMethod('addResource')) {
            $addResource = $reflection->getMethod('addResource');
            $addResource->setAccessible(true);
            foreach ($resources as $resource) {
                $addResource->invoke(
                    $this->translator,
                    'database',
                    'DB',
                    $resource['locale'],
                    $resource['domain'] ?? 'messages'
                );
            }
        }

        $this->isResourcesLoaded = true;
    }

    /**
     * Remove the cache file corresponding to the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function removeCacheFile(string $locale): bool
    {
        if (!file_exists($this->cacheFile)) {
            return true;
        }

        $localeExploded = explode('_', $locale);
        $finder = new Finder();
        $finder->files()->in($this->options['cache_dir'])->name(sprintf('/catalogue\.%s.*\.php$/', $localeExploded[0]));
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
     * @param string $path
     *
     * @throws \RuntimeException
     */
    protected function invalidateSystemCacheForFile(string $path): void
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
     * Get the decorated translator instance.
     * Useful for accessing methods not in TranslatorInterface.
     */
    public function getDecoratedTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
