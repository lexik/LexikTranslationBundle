<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Query\Builder;

use Lexik\Bundle\TranslationBundle\Model\File as ModelFile;

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

        $domains = array();
        foreach ($results as $item) {
            $domains[] = $item;
        }

        sort($domains);

        return $domains;
    }

    /**
     * Returns all domains for each locale.
     *
     * @return array
     */
    public function getAllDomainsByLocale()
    {
        $reduce = <<<FCT
function (obj, prev) {
    if (typeof(prev.couples) == 'undefined') { prev.couples = new Array(); }
    obj.translations.forEach(function (translation) {
        var i = 0, found = false;
        while (i<prev.couples.length && !found) {
            found = (prev.couples[i].locale == translation['locale'] && prev.couples[i].domain == obj.domain);
            i++;
        }
        if (!found) { prev.couples.push({"locale": translation['locale'], "domain": obj.domain}); }
    });
}
FCT;

        $results = $this->createQueryBuilder()
            ->hydrate(false)
            ->group(array(), array('couples' => array()))
            ->reduce($reduce)
            ->sort(array('translations.locale' => 'asc', 'domain' => 'asc'))
            ->getQuery()
            ->execute();

        $couples = array();

        if (isset($results[0], $results[0]['couples'])) {
            $couples = $results[0]['couples'];

            usort($couples, function($a, $b) {
                $result = strcmp($a['locale'], $b['locale']);
                if (0 === $result) {
                    $result = strcmp($a['domain'], $b['domain']);
                }
                return $result;
            });
        }

        return $couples;
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

        $values = array();
        foreach ($results as $item) {
            $i = 0;
            $index = null;
            while ($i<$item['translations'] && null === $index) {
                if ($item['translations'][$i]['locale'] == $locale) {
                    $index = $i;
                }
                $i++;
            }
            $item['translations'] = array($item['translations'][$i-1]);
            $values[] = $item;
        }

        return $values;
    }

    /**
     * Returns some trans units with their translations.
     *
     * @param array $locales
     * @param int   $rows
     * @param int   $page
     * @param array $filters
     * @return array
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        $sortColumn = isset($filters['sidx']) ? $filters['sidx'] : 'id';
        $order = isset($filters['sord']) ? $filters['sord'] : 'ASC';

        $builder = $this->createQueryBuilder()
            ->hydrate(false)
            ->select('id');

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $results = $builder->sort($sortColumn, $order)
            ->skip($rows * ($page-1))
            ->limit($rows)
            ->getQuery()
            ->execute();

        $ids = array();
        foreach ($results as $result) {
            $ids[] = $result['_id'];
        }

        $transUnits = array();

        if (count($ids) < 1) {
            return $transUnits;
        }

        $results = $this->createQueryBuilder()
            ->hydrate(false)
            ->field('id')->in($ids)
            ->field('translations.locale')->in($locales)
            ->sort(array($sortColumn => $order, 'translations.locale' => 'asc'))
            ->getQuery()
            ->execute();

        foreach ($results as $item) {
            $end = count($item['translations']);
            for ($i=0; $i<$end; $i++) {
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
     *
     * @param array $locales
     * @param array $filters
     * @return int
     */
    public function count(array $locales = null,  array $filters = null)
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
     * @param ModelFile $file
     * @param boolean   $onlyUpdated
     * @return array
     */
    public function getTranslationsForFile(ModelFile $file, $onlyUpdated)
    {
        $builder = $this->createQueryBuilder()
            ->hydrate(false)
            ->select('key', 'translations')
            ->field('translations.file.$id')->equals(new \MongoId($file->getId()))
            ->sort('translations.created_at', 'asc');

        $results = $builder->getQuery()->execute();

        $translations = array();
        foreach ($results as $result) {
            $content = null;
            $i = 0;
            while ($i<count($result['translations']) && null === $content) {
                if ($file->getLocale() == $result['translations'][$i]['locale']) {
                    if ($onlyUpdated) {
                        $updated = ($result['translations'][$i]['created_at']->sec < $result['translations'][$i]['updated_at']->sec);
                        $content = $updated ? $result['translations'][$i]['content'] : null;
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
     *
     * @param Builder $builder
     * @param array   $filters
     */
    protected function addTransUnitFilters(Builder $builder,  array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {

            if (!empty($filters['domain'])) {
                $builder->addAnd($builder->expr()->field('domain')->equals(new \MongoRegex(sprintf('/%s/i', $filters['domain']))));
            }

            if (!empty($filters['key'])) {
                $builder->addAnd($builder->expr()->field('key')->equals(new \MongoRegex(sprintf('/%s/i', $filters['key']))));
            }
        }
    }

    /**
     * Add conditions according to given filters.
     *
     * @param Builder $builder
     * @param array   $locales
     * @param array   $filters
     */
    protected function addTranslationFilter(Builder $builder, array $locales = null,  array $filters = null)
    {
        if (null !== $locales) {
            $qb = $this->createQueryBuilder()
                ->hydrate(false)
                ->distinct('id')
                ->field('translations.locale')->in($locales);

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $builder->addAnd(
                        $builder->expr()
                            ->field('translations.content')->equals(new \MongoRegex(sprintf('/%s/i', $filters[$locale])))
                            ->field('translations.locale')->equals($locale)
                    );
                }
            }

            $ids = $qb->getQuery()->execute();

            if (count($ids) > 0) {
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
}
