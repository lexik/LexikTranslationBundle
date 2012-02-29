<?php

namespace Lexik\Bundle\TranslationBundle\Repository\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Query\Builder;

use Lexik\Bundle\TranslationBundle\Repository\TransUnitRepositoryInterface;

/**
 * Repository for TransUnit document.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepository extends DocumentRepository implements TransUnitRepositoryInterface
{
    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Repository.TransUnitRepositoryInterface::getAllDomains()
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
    * (non-PHPdoc)
    * @see Lexik\Bundle\TranslationBundle\Repository.TransUnitRepositoryInterface::getAllDomainsByLocale()
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

        $couples = $results['retval'][0]['couples'];

        usort($couples, function($a, $b) { // @todo remove usort()
            $result = strcmp($a['locale'], $b['locale']);
            if (0 === $result) {
                $result = strcmp($a['domain'], $b['domain']);
            }
            return $result;
        });

        return $couples;
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Repository.TransUnitRepositoryInterface::getAllByLocaleAndDomain()
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
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Repository.TransUnitRepositoryInterface::getTransUnitList()
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        $sortColumn = isset($filters['sidx']) ? $filters['sidx'] : 'id';
        $order = isset($filters['sord']) ? $filters['sord'] : 'ASC';

        $builder = $this->createQueryBuilder()
            ->hydrate(false)
            ->select('id');

        $this->addTransUnitFilters($builder, $locales, $filters);
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

        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder();

            $results = $qb->hydrate(false)
                ->field('id')->in($ids)
                ->field('translations.locale')->in($locales)
                ->sort(array($sortColumn => $order, 'translations.locale' => 'asc'))
                ->getQuery()
                ->execute();

            foreach ($results as $item) {
                for ($i=0; $i<count($item['translations']); $i++) {
                    if (!in_array($item['translations'][$i]['locale'], $locales)) {
                        unset($item['translations'][$i]);
                    }
                }
                sort($item['translations']);
                $transUnits[] = $item;
            }
        }

        return $transUnits;
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Repository.TransUnitRepositoryInterface::count()
     */
    public function count(array $locales = null,  array $filters = null)
    {
        $builder = $this->createQueryBuilder();

        $this->addTransUnitFilters($builder, $locales, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $count = $builder->count()
            ->getQuery()
            ->execute();

        return $count;
    }

    /**
     * Add conditions according to given filters.
     *
     * @param Builder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTransUnitFilters(Builder $builder, array $locales = null,  array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            $cmd = $this->getDocumentManager()->getConfiguration()->getMongoCmd();

            if (!empty($filters['domain'])) {
                $builder->addAnd($builder->expr()->field('domain')->operator($cmd.'regex', $filters['domain']));
            }

            if (!empty($filters['key'])) {
                $builder->addAnd($builder->expr()->field('key')->operator($cmd.'regex', $filters['key']));
            }
        }
    }

    /**
     * Add conditions according to given filters.
     *
     * @param Builder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTranslationFilter(Builder $builder, array $locales = null,  array $filters = null)
    {
        if (null != $locales) {
            $qb = $this->createQueryBuilder()
                ->hydrate(false)
                ->distinct('id')
                ->field('translations.locale')->in($locales);

            $cmd = $this->getDocumentManager()->getConfiguration()->getMongoCmd();

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $builder->addAnd(
                        $builder->expr()
                            ->field('translations.content')->operator($cmd.'regex', $filters[$locale])
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
}