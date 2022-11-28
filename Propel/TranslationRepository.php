<?php

namespace Lexik\Bundle\TranslationBundle\Propel;

use Propel\Runtime\Connection\ConnectionWrapper;
use Lexik\Bundle\TranslationBundle\Propel\Map\TranslationTableMap;

/**
 * Repository for Translation entity (Propel).
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationRepository
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
     * @return \DateTime|null
     */
    public function getLatestTranslationUpdatedAt()
    {
        $result = TranslationQuery::create()
                                  ->withColumn(sprintf('MAX(%s)', TranslationTableMap::COL_UPDATED_AT), 'max_updated_at')
                                  ->select(['max_updated_at'])
                                  ->findOne($this->getConnection());

        return !empty($result) ? new \DateTime($result) : null;
    }
}
