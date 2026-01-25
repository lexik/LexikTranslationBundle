<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Loader;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Loader to load translations from the database.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[AsTaggedItem('translation.loader', alias: 'database')]
#[AsAlias(id: 'Lexik\Bundle\TranslationBundle\Translation\Loader')]
class DatabaseLoader implements LoaderInterface
{
    /**
     * Construct.
     */
    public function __construct(
        #[Autowire(service: 'lexik_translation.translation_storage')]
        private readonly StorageInterface $storage,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);

        $transUnits = $this->storage->getTransUnitsByLocaleAndDomain($locale, $domain);

        foreach ($transUnits as $transUnit) {
            foreach ($transUnit['translations'] as $translation) {
                if ($translation['locale'] == $locale) {
                    $catalogue->set($transUnit['key'], $translation['content'], $domain);
                }
            }
        }

        return $catalogue;
    }
}
