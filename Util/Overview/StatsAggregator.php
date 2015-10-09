<?php

namespace Lexik\Bundle\TranslationBundle\Util\Overview;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

/**
 * Class StatsAggregator
 * @package Lexik\Bundle\TranslationBundle\Util\Overview
 */
class StatsAggregator
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var array
     */
    private $managedLocales;

    /**
     * @param StorageInterface $storage
     * @param array            $managedLocales
     */
    public function __construct(StorageInterface $storage, array $managedLocales)
    {
        $this->storage = $storage;
        $this->managedLocales = $managedLocales;
    }

    /**
     * @return array
     */
    public function getStats()
    {
        $stats = array();
        $countByDomains = $this->storage->getCountTransUnitByDomains();

        foreach ($countByDomains as $domain => $total) {
            $stats[$domain] = array();
            $byLocale = $this->storage->getCountTranslationByLocales($domain);

            foreach ($this->managedLocales as $locale) {
                $localeCount = isset($byLocale[$locale]) ? $byLocale[$locale] : 0;

                $stats[$domain][$locale] = array(
                    'keys'       => $total,
                    'translated' => $localeCount,
                    'completed'  => ($total > 0) ? floor(($localeCount / $total) * 100) : 0,
                );
            }
        }

        return $stats;
    }
}