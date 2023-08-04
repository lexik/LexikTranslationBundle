<?php

namespace Lexik\Bundle\TranslationBundle\Propel;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionWrapper;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepository
{
    public function __construct(
        protected ConnectionWrapper $connection
    ) {
    }

    /**
     * @return ConnectionWrapper
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns all files matching a given locale and a given domains.
     *
     * @return array
     */
    public function findForLocalesAndDomains(array $locales, array $domains)
    {
        return FileQuery::create()
                        ->_if(count($locales) > 0)
                        ->filterByLocale($locales, Criteria::IN)
                        ->_endif()
                        ->_if(count($domains) > 0)
                        ->filterByDomain($domains, Criteria::IN)
                        ->_endif()
                        ->find($this->getConnection());
    }
}
