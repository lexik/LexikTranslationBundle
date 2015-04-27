<?php

namespace Lexik\Bundle\TranslationBundle\Propel;

/**
 * Repository for Translation entity (Propel).
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationRepository
{
    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \PDO
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
            ->withColumn(sprintf('MAX(%s)', TranslationPeer::UPDATED_AT), 'max_updated_at')
            ->select(array('max_updated_at'))
            ->findOne($this->getConnection())
        ;

        return !empty($result) ? new \DateTime($result) : null;
    }
}
