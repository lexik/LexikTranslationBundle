<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

/**
 * Manager for translations files.
 */
class LocaleManager implements LocaleManagerInterface
{
    /**
     * @param list<string> $managedLocales
     */
    public function __construct(
        protected array $managedLocales
    ) {
    }

    public function getLocales(): array
    {
        return $this->managedLocales;
    }
}
