<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Loader;

use Doctrine\ORM\EntityManager;

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
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;

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
     * @param Doctrine\ORM\EntityManager $entityManager
     * @param string $class
     * @param boolean $forceLowerCase
     */
    public function __construct(EntityManager $entityManager, $class, $forceLowerCase)
    {
        $this->entityManager = $entityManager;
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

        $transUnits = $this->entityManager->getRepository($this->class)->getAllByLocaleAndDomain($locale, $domain);

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
