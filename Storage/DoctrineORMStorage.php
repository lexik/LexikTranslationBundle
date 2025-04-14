<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver as SQLiteDriver;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

/**
 * Doctrine ORM storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineORMStorage extends AbstractDoctrineStorage
{
    /**
     * Returns true if translation tables exist.
     *
     * @return boolean
     */
    public function translationsTablesExist()
    {
        /** @var EntityManager $em */
        $em = $this->getManager();
        $connection = $em->getConnection();

        // listDatabases() is not available for SQLite
        if (!$connection->getDriver() instanceof SQLiteDriver) {
            // init a tmp connection without dbname/path/url in case it does not exist yet
            $params = $connection->getParams();
            if (isset($params['master'])) {
                $params = $params['master'];
            }

            unset($params['dbname'], $params['path'], $params['url']);

            try {
                $configuration = new Configuration();
                if (class_exists(DefaultSchemaManagerFactory::class)) {
                    $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
                }

                $tmpConnection = DriverManager::getConnection($params, $configuration);
                $schemaManager = method_exists($tmpConnection, 'createSchemaManager')
                    ? $tmpConnection->createSchemaManager()
                    : $tmpConnection->getSchemaManager();

                $dbExists = in_array($connection->getDatabase(), $schemaManager->listDatabases());
                $tmpConnection->close();
            } catch (ConnectionException|\Exception) {
                $dbExists = false;
            }

            if (!$dbExists) {
                return false;
            }
        }

        // checks tables exist
        $tables = [
            $em->getClassMetadata($this->getModelClass('trans_unit'))->getTableName(),
            $em->getClassMetadata($this->getModelClass('translation'))->getTableName(),
        ];

        $schemaManager = method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager();

        return $schemaManager->tablesExist($tables);
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestUpdatedAt()
    {
        return $this->getTranslationRepository()->getLatestTranslationUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTransUnitByDomains()
    {
        $results = $this->getTransUnitRepository()->countByDomains();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['domain']] = (int)$row['number'];
        }

        return $counts;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTranslationByLocales($domain)
    {
        $results = $this->getTranslationRepository()->countByLocales($domain);

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['locale']] = (int)$row['number'];
        }

        return $counts;
    }

    /**
     * Returns the TransUnit repository.
     *
     * @return object
     */
    protected function getTranslationRepository()
    {
        return $this->getManager()->getRepository($this->classes['translation']);
    }
}
