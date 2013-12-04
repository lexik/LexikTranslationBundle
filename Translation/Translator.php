<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
     *
     */
    public function addDatabaseResources()
    {
        $resources = array();
        $file = sprintf('%s/database.resources.php', $this->options['cache_dir']);
        $cache = new ConfigCache($file, $this->options['debug']);

        if (!$cache->isFresh()) {
            $resources = $this->container->get('lexik_translation.translation_storage')->getTransUnitDomainsByLocale();

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
            if (!unlink($file)) {
                $deleted = false;
            }
            $metadata = $file.'.meta';
            if (file_exists($metadata)) {
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
            unlink($file);
        }

        $metadata = $file.'.meta';
        if (file_exists($metadata)) {
            unlink($metadata);
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
