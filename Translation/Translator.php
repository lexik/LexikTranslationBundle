<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\ConfigCache;

/**
 * Translator service class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translator extends BaseTranslator
{
    /**
     * @var boolean
     */
    private $forceLowerCase;

    /**
     * Set forceLowerCase value.
     *
     * @param boolean $value
     */
    public function setForceLowerCase($value)
    {
        $this->forceLowerCase = (boolean) $value;
    }

    /**
     * Get forceLowerCase value.
     *
     * @return boolean
     */
    public function getForceLowerCase()
    {
        return $this->forceLowerCase;
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Translation.TranslatorInterface::trans()
     */
    public function trans($id, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        $id = $this->changeIdCase($id);

        return parent::trans($id, $parameters, $domain, $locale);
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Translation.Translator::transChoice()
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        $id = $this->changeIdCase($id);

        return parent::transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Change id to lowercase if necessary.
     *
     * @param string $id
     */
    protected function changeIdCase($id)
    {
        if ($this->forceLowerCase) {
            $id = mb_strtolower($id, 'UTF-8');
        }

        return $id;
    }

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
            $resources = $this->container->get('lexik_translation.storage_manager')
                ->getRepository($this->container->getParameter('lexik_translation.trans_unit.class'))
                ->getAllDomainsByLocale();

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
     * Update a trans unit element form the given request.
     *
     * @param Request $request
     * @param boolean $removeCache
     */
    public function updateTransUnitFromRequest(Request $request, $removeCache = false)
    {
        $locales = $this->container->getParameter('lexik_translation.managed_locales');
        $repository = $this->container->get('lexik_translation.storage_manager')->getRepository($this->container->getParameter('lexik_translation.trans_unit.class'));
        $transUnitManager = $this->container->get('lexik_translation.trans_unit.manager');

        $transUnit = $repository->findOneById($request->request->get('id'));

        foreach ($locales as $locale) {
            $value = $request->request->get($locale);
            if (!empty($value)) {
                if ($transUnit->hasTranslation($locale)) {
                    $transUnitManager->updateTranslation($transUnit, $locale, $value);
                } else {
                    $transUnitManager->addTranslation($transUnit, $locale, $value);
                }

                if ($removeCache) {
                    $this->removeCacheFile($locale);
                }
            }
        }

        $this->container->get('lexik_translation.storage_manager')->flush();
    }

    /**
     * Remove the cache file corresponding to the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function removeCacheFile($locale)
    {
        $file = sprintf('%s/catalogue.%s.php', $this->options['cache_dir'], $locale);
        $deleted = false;

        if (file_exists($file)) {
            $deleted = unlink($file);
        }

        $metadata = $file.'.meta';
        if (file_exists($metadata)) {
            unlink($metadata);
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
}