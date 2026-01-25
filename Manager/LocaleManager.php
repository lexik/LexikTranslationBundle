<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Manager for translations files.
 */
#[AsAlias(id: 'lexik_translation.locale.manager', public: true)]
class LocaleManager implements LocaleManagerInterface
{
    /**
     * Constructor
     */
    public function __construct(
        #[Autowire('%lexik_translation.managed_locales%')]
        protected array $managedLocales
    ) {
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->managedLocales;
    }
}
