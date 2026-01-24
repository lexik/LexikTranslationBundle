<?php

namespace Lexik\Bundle\TranslationBundle\Util\Overview;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Class StatsAggregator
 * @package Lexik\Bundle\TranslationBundle\Util\Overview
 */
#[AsAlias(id: 'lexik_translation.overview.stats_aggregator', public: true)]
class StatsAggregator
{
    public function __construct(
        #[Autowire(service: 'lexik_translation.translation_storage')]
        private readonly StorageInterface $storage,
        #[Autowire(service: 'Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface')]
        private readonly LocaleManagerInterface $localeManager,
    ) {
    }

    /**
     * @return array
     */
    public function getStats()
    {
        $stats = [];
        $countByDomains = $this->storage->getCountTransUnitByDomains();

        foreach ($countByDomains as $domain => $total) {
            $stats[$domain] = [];
            $byLocale = $this->storage->getCountTranslationByLocales($domain);

            foreach ($this->localeManager->getLocales() as $locale) {
                $localeCount = $byLocale[$locale] ?? 0;

                $stats[$domain][$locale] = ['keys'       => $total,
                                            'translated' => $localeCount,
                                            'completed'  => ($total > 0) ? floor(($localeCount / $total) * 100) : 0,
                ];
            }
        }

        return $stats;
    }
}
