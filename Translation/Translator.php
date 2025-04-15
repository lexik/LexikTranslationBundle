<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Symfony\Contracts\Translation\TranslatorInterface as BaseTranslator;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Finder\Finder;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Formatter\MessageFormatter;

/**
 * Translator service class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translator
{
    public function __construct(
        private BaseTranslator $baseTranslator,
        private ContainerInterface $container,
        private MessageFormatter $formatter,
        private array $loaderIds,
        private string $defaultLocale,
        private array $options
    ) {
        $this->baseTranslator = $baseTranslator;
        $this->container = $container;
        $this->formatter = $formatter;
        $this->loaderIds = $loaderIds;
        $this->defaultLocale = $defaultLocale;
        $this->options = $options;
    }

    /**
     * Add all resources available in database.
     */
    public function addDatabaseResources()
    {
        $file = sprintf('%s/database.resources.php', $this->options['cache_dir']);
        $cache = new ConfigCache($file, $this->options['debug']);

        if (!$cache->isFresh()) {
            $event = new GetDatabaseResourcesEvent();
            $this->container->get('event_dispatcher')->dispatch($event);

            $resources = $event->getResources();
            $metadata = [];

            foreach ($resources as $resource) {
                $metadata[] = new DatabaseFreshResource($resource['locale'], $resource['domain']);
            }

            $this->baseTranslator->addResource('database', 'DB', $resource['locale'], $resource['domain']);
            $content = sprintf("<?php return %s;", var_export($resources, true));
            $cache->write($content, $metadata);
        } else {
            $resources = include $file;
        }

        foreach ($resources as $resource) {
            $this->baseTranslator->addResource('database', 'DB', $resource['locale'], $resource['domain']);
        }
    }

    /**
     * Remove the cache file corresponding to the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function removeCacheFile($locale)
    {
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
}
