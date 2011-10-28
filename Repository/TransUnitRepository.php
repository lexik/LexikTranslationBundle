<?php

namespace Lexik\Bundle\TranslationBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\EntityRepository;

use Lexik\Bundle\TranslationBundle\Entity\TransUnit;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepository extends EntityRepository
{
    /**
     * Returns all trans unit domains for each locale.
     *
     * @return array
     */
    public function getAllDomainsByLocale()
    {
        $dql = 'SELECT te.locale, tu.domain'
            .' FROM %s tu LEFT JOIN tu.translations te'
            .' GROUP BY te.locale, tu.domain';

        return $this->getEntityManager()
            ->createQuery(sprintf($dql, $this->getEntityName()))
            ->getArrayResult();
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
        $dql = 'SELECT tu, te'
            .' FROM %s tu LEFT JOIN tu.translations te'
            .' WHERE tu.domain = :domain AND te.locale = :locale';

        return $this->getEntityManager()
            ->createQuery(sprintf($dql, $this->getEntityName()))
            ->setParameter('domain', $domain)
            ->setParameter('locale', $locale)
            ->getArrayResult();
    }

    /**
     * Return all domain available in database.
     *
     * @return array
     */
    public function getAllDomains()
    {
        $this->loadCustomHydrator();

        $dql = 'SELECT DISTINCT tu.domain'
            .' FROM %s tu'
            .' ORDER BY tu.domain ASC';

        return $this->getEntityManager()
            ->createQuery(sprintf($dql, $this->getEntityName()))
            ->getResult('SingleColumnArrayHydrator');
    }

    /**
     * Returns some trans units with their translations.
     *
     * @param array $locales
     * @param int $rows
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        $this->loadCustomHydrator();

        $sortColumn = isset($filters['sidx']) ? $filters['sidx'] : 'id';
        $order = isset($filters['sord']) ? $filters['sord'] : 'ASC';

        $builder = $this->createQueryBuilder('tu')
            ->select('tu.id');

        $this->addTransUnitFilters($builder, $locales, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $ids = $builder->orderBy(sprintf('tu.%s', $sortColumn), $order)
            ->setFirstResult($rows * ($page-1))
            ->setMaxResults($rows)
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');

        $transUnits = array();

        if (count($ids) > 0) {
            $transUnits = $this->createQueryBuilder('tu')
                ->select('tu, te')
                ->leftJoin('tu.translations', 'te')
                ->andWhere(sprintf('tu.id IN (%s)', implode(', ', $ids)))
                ->andWhere(sprintf('te.locale IN (\'%s\')', implode('\', \'', $locales)))
                ->orderBy(sprintf('tu.%s', $sortColumn), $order)
                ->getQuery()
                ->getArrayResult();
        }

        return $transUnits;
    }

    /**
     * Count the number of transunit.
     *
     * @param array $locales
     * @param array $filters
     * @return int
     */
    public function count(array $locales = null,  array $filters = null)
    {
        $this->loadCustomHydrator();

        $builder = $this->createQueryBuilder('tu')
            ->select('COUNT(DISTINCT tu.id) AS number');

        $this->addTransUnitFilters($builder, $locales, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        return (int) $builder->getQuery()
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Add conditions according to given filters.
     *
     * @param QueryBuilder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTransUnitFilters(QueryBuilder $builder, array $locales = null,  array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            if (!empty($filters['domain'])) {
                $builder->andWhere('tu.domain LIKE :domain')
                    ->setParameter('domain', sprintf('%%%s%%', $filters['domain']));
            }

            if (!empty($filters['key'])) {
                $builder->andWhere('tu.key LIKE :key')
                    ->setParameter('key', sprintf('%%%s%%', $filters['key']));
            }
        }
    }

    /**
     * Add conditions according to given filters.
     *
     * @param QueryBuilder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTranslationFilter(QueryBuilder $builder, array $locales = null,  array $filters = null)
    {
        if (null != $locales) {
            $sql = sprintf('SELECT DISTINCT t.trans_unit_id FROM %s t WHERE t.locale IN (\'%s\')',
                $this->getEntityManager()->getClassMetadata('Lexik\Bundle\TranslationBundle\Entity\Translation')->getTableName(),
                implode('\', \'', $locales)
            );

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $sql .= sprintf(' AND (t.content LIKE \'%%%s%%\' AND t.locale = \'%s\')', $filters[$locale], $locale);
                }
            }

            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('trans_unit_id', 'transUnit');

            $ids = $this->getEntityManager()
                ->createNativeQuery($sql, $rsm)
                ->getResult('SingleColumnArrayHydrator');

            if (count($ids) > 0) {
                $builder->andWhere(sprintf('tu.id IN (%s)', implode(', ', $ids)));
            }
        }
    }

    /**
     * Load custom hydrator.
     */
    protected function loadCustomHydrator()
    {
        $config = $this->getEntityManager()->getConfiguration();
        $config->addCustomHydrationMode('SingleColumnArrayHydrator', 'Lexik\Bundle\TranslationBundle\Hydrators\SingleColumnArrayHydrator');
    }
}