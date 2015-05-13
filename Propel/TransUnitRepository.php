<?php

namespace Lexik\Bundle\TranslationBundle\Propel;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepository
{
    /**
     * @var \PDO
     */
    protected $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return PDO
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns all domain available in database.
     *
     * @return array
     */
    public function getAllDomainsByLocale()
    {
        return TransUnitQuery::create()
            ->joinWith('Translation')
            ->withColumn('Translation.Locale', 'locale')
            ->withColumn('Domain', 'domain')
            ->select(array('locale', 'domain'))
            ->groupBy('locale')
            ->groupBy('domain')
            ->find($this->getConnection())
            ->getArrayCopy()
        ;
    }

    /**
     * Returns all domains for each locale.
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getAllByLocaleAndDomain($locale, $domain)
    {
        $unitsData = TransUnitQuery::create()
            ->filterByDomain($domain)
            ->joinWith('Translation')
            ->useTranslationQuery()
                ->filterByLocale($locale)
            ->endUse()
            ->setFormatter('PropelArrayFormatter')
            ->find($this->getConnection())
        ;

        return $this->filterTransUnitData($unitsData);
    }

    /**
     * Returns all trans unit with translations for the given domain and locale.
     *
     * @return array
     */
    public function getAllDomains()
    {
        $domains = TransUnitQuery::create()
            ->select('Domain')
            ->setDistinct()
            ->orderByDomain(\Criteria::ASC)
            ->find($this->getConnection())
        ;

        return array_values($domains->getArrayCopy());
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

        $sortColumn = ucfirst($sortColumn);

        $query = TransUnitQuery::create()
            ->select('Id')
        ;

        $this->addTransUnitFilters($query, $filters);
        $this->addTranslationFilter($query, $locales, $filters);

        $ids = $query
            ->orderBy($sortColumn, $order)
            ->offset($rows * ($page-1))
            ->limit($rows)
            ->find($this->getConnection())
        ;

        $transUnits = array();

        if (count($ids) > 0) {
            $unitsData = TransUnitQuery::create()
                ->filterById($ids, \Criteria::IN)
                ->joinWith('Translation')
                ->useTranslationQuery()
                    ->filterByLocale($locales, \Criteria::IN)
                ->endUse()
                ->orderBy($sortColumn, $order)
                ->setFormatter('PropelArrayFormatter')
                ->find($this->getConnection())
            ;

            $transUnits = $this->filterTransUnitData($unitsData);
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
        $query = TransUnitQuery::create()
            ->select('Id')
            ->distinct()
        ;

        $this->addTransUnitFilters($query, $filters);
        $this->addTranslationFilter($query, $locales, $filters);

        return $query->count($this->getConnection());
    }

    /**
     * Returns all translations for the given file.
     *
     * @param File      $file
     * @param boolean   $onlyUpdated
     *
     * @return array
     */
    public function getTranslationsForFile($file, $onlyUpdated)
    {
        $query = TranslationQuery::create()
            ->filterByFile($file)
            ->joinWith('TransUnit')
        ;

        if ($onlyUpdated) {
            $query->add(null, TranslationPeer::UPDATED_AT.'>'.TranslationPeer::CREATED_AT, \Criteria::CUSTOM);
        }

        $results = $query
            ->select(array('Content', 'TransUnit.Key'))
            ->orderBy(TranslationPeer::ID, \Criteria::ASC)
            ->find()
        ;

        $translations = array();
        foreach ($results as $result) {
            $translations[$result['TransUnit.Key']] = $result['Content'];
        }

        return $translations;
    }

    /**
     * Add conditions according to given filters.
     *
     * @param TransUnitQuery    $query
     * @param array             $filters
     */
    protected function addTransUnitFilters(TransUnitQuery $query, array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            if (!empty($filters['domain'])) {
                $query->filterByDomain(sprintf('%%%s%%', $filters['domain']), \Criteria::LIKE);
            }

            if (!empty($filters['key'])) {
                $query->filterByKey(sprintf('%%%s%%', $filters['key']), \Criteria::LIKE);
            }
        }
    }

    /**
     * Add conditions according to given filters.
     *
     * @param TransUnitQuery    $query
     * @param array             $locales
     * @param array             $filters
     */
    protected function addTranslationFilter(TransUnitQuery $query, array $locales = null, array $filters = null)
    {
        if (null !== $locales) {
            $q = TransUnitQuery::create()
                ->select('Id')
                ->distinct()
                ->join('Translation', \Criteria::LEFT_JOIN)
                ->useTranslationQuery()
                    ->filterByLocale($locales, \Criteria::IN)
            ;

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $q
                        ->filterByContent(sprintf('%%%s%%', $filters[$locale]), \Criteria::LIKE)
                        ->filterByLocale(sprintf('%s', $locale))
                    ;
                }
            }

            $ids = $q
                ->endUse()
                ->find($this->getConnection())
            ;

            if (count($ids) > 0) {
                $query->filterById($ids, \Criteria::IN);
            }
        }
    }

    /**
     * Convert transUnit data with nested translations into the required format.
     *
     * @param array|PropelArrayCollection $transUnitData
     * @return array
     */
    protected function filterTransUnitData($unitsData)
    {
        $cleaned = array();

        foreach ($unitsData as $unit) {
            /* @var $unit TransUnit */
            $transUnit = array(
                'id' => $unit['Id'],
                'key' => $unit['Key'],
                'domain' => $unit['Domain'],
                'translations' => array(),
            );

            foreach ($unit['Translations'] as $translation)
            {
                $transUnit['translations'][] = array(
                    'locale' => $translation['Locale'],
                    'content' => $translation['Content'],
                );
            }

            $cleaned[] = $transUnit;
        }

        return $cleaned;
    }
}
