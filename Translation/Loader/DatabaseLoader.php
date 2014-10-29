<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Loader;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Loader to load translations from the database.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseLoader implements LoaderInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var AppKernel
     */
    private $kernel;

    /**
     * Construct.
     *
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage, \AppKernel $kernel)
    {
        $this->storage = $storage;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);

        $client = $this->kernel->getFolderClient();
        $transUnits = $this->storage->getTransUnitsByLocaleAndDomainAndClient($locale, $domain, $client);

        foreach ($transUnits as $transUnit) {
            foreach ($transUnit['translations'] as $translation) {
                if($translation['locale'] == $locale) {
                    $catalogue->set($transUnit['key'], $translation['content'], $domain);
                }
            }
        }

        return $catalogue;
    }
}
