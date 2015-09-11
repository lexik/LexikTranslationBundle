<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

/**
 * Manager for translations files.
 */
class LocaleManager implements LocaleManagerInterface
{
    /**
     * @var array
     */
    protected $managedLocales;

    /**
     * Constructor
     *
     * @param array $managedLocales
     */
    public function __construct(array $managedLocales)
    {
        $this->managedLocales = $managedLocales;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->managedLocales;
    }
}
