<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Lexik\Bundle\TranslationBundle\Model\File as ModelFile;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

/**
 * Repository for TransUnit document.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepository extends DocumentRepository
{
    /**
     * Returns all domain available in database.
     *
     * @return array
     */
    public function getAllDomains()
    {
        $results = $this->createQueryBuilder()
                        ->distinct('domain')
                        ->sort('domain', 'asc')
                        ->hydrate(false)
                        ->getQuery()
                        ->execute();

        $domains = [];
        foreach ($results as $item) {
            $domains[] = $item;
        }

        sort($domains);

        return $domains;
    }

    /**
     * Returns all domains for each locale.
     */
    public function getAllDomainsByLocale(): array
    {
        $aggregationBuilder = $this->createAggregationBuilder();
        $results = $aggregationBuilder
            ->group()
            ->field('_id')
            ->expression('$domain')
            ->field('locales')
            // @see https://docs.mongodb.com/manual/reference/operator/aggregation/addToSet/#grp._S_addToSet
            ->expression(['$addToSet' => '$translations.locale'])
            ->execute();

        //return $results->toArray();
        /*
         * Example of $results->toArray():
         *
         * Array &0 (
             0 => Array &1 (
                 '_id' => 'messages'
                 'locales' => Array &2 (
                     0 => Array &3 (
                         0 => 'fr'
                         1 => 'en'
                     )
                     1 => Array &4 (
                         0 => 'za'
                         1 => 'en'
                     )
                 )
             )
             1 => Array &5 (
                 '_id' => 'superTranslations'
                 'locales' => Array &6 (
                     0 => Array &7 (
                         0 => 'fr'
                         1 => 'en'
                         2 => 'de'
                     )
                 )
             )
         )
         */

        $domainGroups = $results->toArray();

        $domainsByLocale = [];

        foreach ($domainGroups as $domainGroup) {
            if (!\is_array($domainGroup['locales'])) {
                continue;
            }

            $domain = $domainGroup['_id'];
            // Flatten the locales array - handle both array of arrays and array of strings
            $locales = [];
            foreach ($domainGroup['locales'] as $localeItem) {
                if (\is_array($localeItem)) {
                    $locales = \array_merge($locales, $localeItem);
                } else {
                    $locales[] = $localeItem;
                }
            }
            // Remove duplicates
            $locales = \array_unique($locales);

            foreach ($locales as $locale) {
                $domainsByLocale[] = [
                    'locale' => $locale,
                    'domain' => $domain,
                ];
            }
        }

        usort($domainsByLocale, function ($a, $b) {
            $result = strcmp((string) $a['locale'], (string) $b['locale']);
            if (0 === $result) {
                $result = strcmp((string) $a['domain'], (string) $b['domain']);
            }
            return $result;
        });

        return $domainsByLocale;
    }

    /**
     * Returns all trans unit with translations for the given domain and locale.
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getAllByLocaleAndDomain($locale, $domain)
    {
        $results = $this->createQueryBuilder()
                        ->hydrate(false)
                        ->field('domain')->equals($domain)
                        ->field('translations.locale')->equals($locale)
                        ->sort('key', 'asc')
                        ->getQuery()
                        ->execute();

        $values = [];
        foreach ($results as $item) {
            $i = 0;
            $index = null;
            while ($i < $item['translations'] && null === $index) {
                if ($item['translations'][$i]['locale'] == $locale) {
                    $index = $i;
                }
                $i++;
            }
            $item['translations'] = [$item['translations'][$i - 1]];
            $values[] = $item;
        }

        return $values;
    }

    /**
     * Returns some trans units with their translations.
     */
    public function getTransUnitList(?array $locales = null, int $rows = 20, int $page = 1, ?array $filters = null): array
    {
        $sortColumn = $filters['sidx'] ?? 'id';
        $order = $filters['sord'] ?? 'ASC';

        $builder = $this->createQueryBuilder()
                        ->hydrate(false)
                        ->select('id');

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $results = $builder->sort($sortColumn, $order)
                           ->skip($rows * ($page - 1))
                           ->limit($rows)
                           ->getQuery()
                           ->execute();

        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result['_id'];
        }

        $transUnits = [];

        if (count($ids) < 1) {
            return $transUnits;
        }

        $results = $this->createQueryBuilder()
                        ->hydrate(false)
                        ->field('id')->in($ids)
                        ->field('translations.locale')->in($locales)
                        ->sort([$sortColumn => $order, 'translations.locale' => 'asc'])
                        ->getQuery()
                        ->execute();

        foreach ($results as $item) {
            $end = is_countable($item['translations']) ? count($item['translations']) : 0;
            for ($i = 0; $i < $end; $i++) {
                if (!in_array($item['translations'][$i]['locale'], $locales)) {
                    unset($item['translations'][$i]);
                }
            }
            sort($item['translations']);
            $transUnits[] = $item;
        }

        return $transUnits;
    }

    /**
     * Count the number of trans unit.
     */
    public function count(?array $locales = null, ?array $filters = null): int
    {
        $builder = $this->createQueryBuilder();

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $count = $builder->count()
                         ->getQuery()
                         ->execute();

        return $count;
    }

    /**
     * Returns all translations for the given file.
     *
     * @param boolean   $onlyUpdated
     * @return array
     */
    public function getTranslationsForFile(ModelFile $file, $onlyUpdated)
    {
        $builder = $this->createQueryBuilder()
                        ->hydrate(false)
                        ->select('key', 'translations')
                        ->field('translations.file.$id')->equals(new ObjectId($file->getId()))
                        ->sort('translations.created_at', 'asc');

        $results = $builder->getQuery()->execute();

        $translations = [];
        foreach ($results as $result) {
            $content = null;
            $i = 0;
            while ($i < (is_countable($result['translations']) ? count($result['translations']) : 0) && null === $content) {
                if ($file->getLocale() == $result['translations'][$i]['locale']) {
                    if ($onlyUpdated) {
                        // Handle MongoDB Timestamp objects - they have a 'sec' property
                        $createdAt = $result['translations'][$i]['createdAt'] ?? null;
                        $updatedAt = $result['translations'][$i]['updatedAt'] ?? null;
                        
                        if ($createdAt && $updatedAt) {
                            $createdAtSec = \is_object($createdAt) && \property_exists($createdAt, 'sec') ? $createdAt->sec : $createdAt;
                            $updatedAtSec = \is_object($updatedAt) && \property_exists($updatedAt, 'sec') ? $updatedAt->sec : $updatedAt;
                            $updated = ($createdAtSec < $updatedAtSec);
                            $content = $updated ? $result['translations'][$i]['content'] : null;
                        }
                    } else {
                        $content = $result['translations'][$i]['content'];
                    }
                }
                $i++;
            }

            if (null !== $content) {
                $translations[$result['key']] = $content;
            }
        }

        return $translations;
    }

    /**
     * Add conditions according to given filters.
     */
    protected function addTransUnitFilters(Builder $builder, ?array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            if (!empty($filters['domain'])) {
                $regex = new Regex($filters['domain'], 'i');
                $builder->addAnd($builder->expr()->field('domain')->equals($regex));
            }

            if (!empty($filters['key'])) {
                $regex = new Regex($filters['key'], 'i');
                $builder->addAnd($builder->expr()->field('key')->equals($regex));
            }
        }
    }

    /**
     * Add conditions according to given filters.
     */
    protected function addTranslationFilter(Builder $builder, ?array $locales = null, ?array $filters = null)
    {
        if (null !== $locales) {
            $qb = $this->createQueryBuilder()
                       ->hydrate(false)
                       ->distinct('id')
                       ->field('translations.locale')->in($locales);

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $regex = new Regex($filters[$locale], 'i');
                    $builder->addAnd(
                        $builder->expr()
                                ->field('translations.content')->equals($regex)
                                ->field('translations.locale')->equals($locale)
                    );
                }
            }

            $ids = $qb->getQuery()->execute();
            //$ids = iterator_to_array($ids);

            if (($ids === null ? 0 : count($ids)) > 0) {
                $builder->field('id')->in($ids);
            }
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getLatestTranslationUpdatedAt()
    {
        $result = $this->createQueryBuilder()
                       ->hydrate(false)
                       ->select('translations.updatedAt')
                       ->sort('translations.updatedAt', 'desc')
                       ->limit(1)
                       ->getQuery()
                       ->getSingleResult();

        if (!isset($result['translations'], $result['translations'][0])) {
            return null;
        }

        return new \DateTime(date('Y-m-d H:i:s', $result['translations'][0]['updated_at']->sec));
    }

    /**
     * @return array
     */
    public function countByDomains()
    {
        $reduce = <<<FCT
function (obj, prev) {
    if (typeof(prev.count) == 'undefined') { prev.count = {}; }

    if (!prev.count.hasOwnProperty(obj.domain)) {
        prev.count[obj.domain] = 1;
    } else {
        prev.count[obj.domain]++;
    }
}
FCT;

        $results = $this->createQueryBuilder()
                        ->group([],
                            []) // @todo: group and reduce won't work anymore, but this method seems to be untested
                        ->reduce($reduce)
                        ->hydrate(false)
                        ->getQuery()
                        ->execute();

        return $results[0]['count'] ?? [];
    }

    /**
     * @param string $domain
     * @return array
     */
    public function countTranslationsByLocales($domain)
    {
        $reduce = <<<FCT
function (obj, prev) {
    if (typeof(prev.count) == 'undefined') { prev.count = {}; }

    if (obj.translations) {
        obj.translations.forEach(function (translation) {
            if (!prev.count.hasOwnProperty(translation.locale)) {
                prev.count[translation.locale] = 1;
            } else {
                prev.count[translation.locale]++;
            }
        });
    }
}
FCT;

        $results = $this->createQueryBuilder()
                        ->field('domain')->equals($domain)
                        ->group([], []) // @todo: won't work, untested
                        ->reduce($reduce)
                        ->hydrate(false)
                        ->getQuery()
                        ->execute();

        return $results[0]['count'] ?? [];
    }
}
