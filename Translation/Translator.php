<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Config\ConfigCache;

/**
 * Translator service class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translator extends BaseTranslator
{
    /**
     * Add all resources available in database.
     */
    public function addDatabaseResources()
    {
        $resources = array();
        $file = sprintf('%s/database.resources.php', $this->options['cache_dir']);
        $cache = new ConfigCache($file, $this->options['debug']);

        if (!$cache->isFresh()) {
            $event = new GetDatabaseResourcesEvent();
            $this->container->get('event_dispatcher')->dispatch('lexik_translation.event.get_database_resources', $event);

            $resources = $event->getResources();
            $metadata = array();

            foreach ($resources as $resource) {
                $metadata[] = new DatabaseFreshResource($resource['locale'], $resource['domain']);
            }

            $content = sprintf("<?php return %s;", var_export($resources, true));
            $cache->write($content, $metadata);
        } else {
            $resources = include $file;
        }

        foreach($resources as $resource) {
            $this->addResource('database', 'DB', $resource['locale'], $resource['domain']);
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
        $localePattern = sprintf('%s/catalogue.*%s*.php', $this->options['cache_dir'], $localeExploded[0]);
        $files = glob($localePattern);

        $deleted = true;
        foreach ($files as $file) {
            if (file_exists($file)) {
                $this->invalidateSystemCacheForFile($file);
                $deleted = unlink($file);
            }

            $metadata = $file.'.meta';
            if (file_exists($metadata)) {
                $this->invalidateSystemCacheForFile($metadata);
                unlink($metadata);
            }
        }

        return $deleted;
    }

    /**
     * Remove the cache file corresponding to each given locale.
     *
     * @param array $locales
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
        if (ini_get('apc.enabled')) {
            if (apc_exists($path) && !apc_delete_file($path)) {
                throw new \RuntimeException(sprintf('Failed to clear APC Cache for file %s', $path));
            }
        } elseif ('cli' === php_sapi_name() ? ini_get('opcache.enable_cli') : ini_get('opcache.enable')) {
            if (!opcache_invalidate($path, true)) {
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
        $allFormats = array();

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

        while($i<count($ids) && null === $loader) {
            if (in_array($format, $this->loaderIds[$ids[$i]])) {
                $loader = $this->container->get($ids[$i]);
            }
            $i++;
        }

        if ( !($loader instanceof LoaderInterface) ) {
            throw new \RuntimeException(sprintf('No loader found for "%s" format.', $format));
        }

        return $loader;
    }
}
