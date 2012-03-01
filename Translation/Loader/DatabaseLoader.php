<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Loader;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Loader to load translations from the database.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseLoader implements LoaderInterface
{
    /**
     * @var Doctrine\Common\Persistence\ObjectManager
     */
    private $objectManager;

    /**
     * TransUnit entity class.
     * @var string
     */
    private $class;

    /**
     * @var boolean
     */
    private $forceLowerCase;

    /**
     * Construct.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param string $class
     * @param boolean $forceLowerCase
     */
    public function __construct(ObjectManager $objectManager, $class, $forceLowerCase)
    {
        $this->objectManager = $objectManager;
        $this->class = $class;
        $this->forceLowerCase = $forceLowerCase;
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Translation\Loader.LoaderInterface::load()
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);

        $transUnits = $this->objectManager->getRepository($this->class)->getAllByLocaleAndDomain($locale, $domain);

        foreach ($transUnits as $transUnit) {
            foreach ($transUnit['translations'] as $translation) {
                if($translation['locale'] == $locale) {
                    $key = $this->forceLowerCase ? mb_strtolower($transUnit['key'], 'UTF-8') : $transUnit['key'];
                    $catalogue->set($key, $translation['content'], $domain);
                }
            }
        }

        return $catalogue;
    }
}
