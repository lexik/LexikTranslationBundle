<?php

namespace Lexik\Bundle\TranslationBundle\Propel;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepository
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
     * Returns all files matching a given locale and a given domains.
     *
     * @param array $locales
     * @param array $domains
     * @return array
     */
    public function findForLocalesAndDomains(array $locales, array $domains)
    {
        return FileQuery::create()
            ->_if(count($locales) > 0)
                ->filterByLocale($locales, \Criteria::IN)
            ->_endif()

            ->_if(count($domains) > 0)
                ->filterByDomain($domains, \Criteria::IN)
            ->_endif()

            ->find($this->getConnection())
        ;
    }
}
