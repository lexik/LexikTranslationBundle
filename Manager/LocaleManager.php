<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

/**
 * Manager for translations files.
 */
class LocaleManager implements LocaleManagerInterface
{
    /**
     * Constructor
     */
    public function __construct(
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
