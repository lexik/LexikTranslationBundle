<?php

namespace Lexik\Bundle\TranslationBundle\Util\Overview;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
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
     * @var LocaleManagerInterface
     */
    private $localeManager;

    /**
     * @param StorageInterface       $storage
     * @param LocaleManagerInterface $localeManager
     */
    public function __construct(StorageInterface $storage, LocaleManagerInterface $localeManager)
    {
        $this->storage = $storage;
        $this->localeManager = $localeManager;
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

            foreach ($this->localeManager->getLocales() as $locale) {
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