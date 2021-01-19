<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit;

use Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\TranslationBundle\Storage\DoctrineMongoDBStorage;
use Lexik\Bundle\TranslationBundle\Storage\DoctrineORMStorage;
use Lexik\Bundle\TranslationBundle\Storage\PropelStorage;
use Lexik\Bundle\TranslationBundle\Tests\Fixtures\TransUnitData;
use Lexik\Bundle\TranslationBundle\Tests\Fixtures\TransUnitDataPropel;
use MongoDB\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Propel;

/**
 * Base unit test class providing functions to create a mock entity manger, load schema and fixtures.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class BaseUnitTestCase extends TestCase
{
    const ENTITY_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Entity\TransUnit';
    const ENTITY_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Entity\Translation';
    const ENTITY_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Entity\File';

    const DOCUMENT_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Document\TransUnit';
    const DOCUMENT_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Document\Translation';
    const DOCUMENT_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Document\File';

    const PROPEL_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Propel\TransUnit';
    const PROPEL_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Propel\Translation';
    const PROPEL_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Propel\File';

    /**
     * Create a storage class form doctrine ORM.
     *
     * @param EntityManager $em
     * @return DoctrineORMStorage
     */
    protected function getORMStorage(EntityManager $em)
    {
        $registryMock = $this->getDoctrineRegistryMock($em);

        $storage = new DoctrineORMStorage($registryMock, 'default', [
            'trans_unit' => self::ENTITY_TRANS_UNIT_CLASS,
            'translation' => self::ENTITY_TRANSLATION_CLASS,
            'file' => self::ENTITY_FILE_CLASS,
        ]);

        return $storage;
    }

    /**
     * Create a storage class form doctrine Mongo DB.
     */
    protected function getMongoDBStorage(DocumentManager $dm): DoctrineMongoDBStorage
    {
        $registryMock = $this->getDoctrineRegistryMock($dm);

        $storage = new DoctrineMongoDBStorage($registryMock, 'default', [
            'trans_unit' => self::DOCUMENT_TRANS_UNIT_CLASS,
            'translation' => self::DOCUMENT_TRANSLATION_CLASS,
            'file' => self::DOCUMENT_FILE_CLASS,
        ]);

        return $storage;
    }

    /**
     * Create a storage class for Propel.
     *
     * @return PropelStorage
     */
    protected function getPropelStorage()
    {
        $storage = new PropelStorage(null, [
            'trans_unit' => self::PROPEL_TRANS_UNIT_CLASS,
            'translation' => self::PROPEL_TRANSLATION_CLASS,
            'file' => self::PROPEL_FILE_CLASS,
        ]);

        return $storage;
    }

    /**
     * Create the database schema.
     *
     * @param ObjectManager $om
     */
    protected function createSchema(ObjectManager $om)
    {
        if ($om instanceof EntityManager) {
            $schemaTool = new SchemaTool($om);
            $schemaTool->createSchema($om->getMetadataFactory()->getAllMetadata());
        } elseif ($om instanceof DocumentManager) {
            $sm = new SchemaManager($om, $om->getMetadataFactory());
            $sm->dropDatabases();
            $sm->createCollections();
        }
    }

    /**
     * Load test fixtures.
     *
     * @param ObjectManager $om
     */
    protected function loadFixtures(ObjectManager $om)
    {
        if ($om instanceof EntityManager) {
            $purger = new ORMPurger();
            $executor = new ORMExecutor($om, $purger);
        } elseif ($om instanceof DocumentManager) {
            $purger = new MongoDBPurger();
            $executor = new MongoDBExecutor($om, $purger);
        }

        $fixtures = new TransUnitData();
        $executor->execute([$fixtures], false);
    }

    /**
     * Load test fixtures for Propel.
     */
    protected function loadPropelFixtures(ConnectionWrapper $con)
    {
        $fixtures = new TransUnitDataPropel();
        $fixtures->load($con);
    }

    /**
     * @param $om
     * @return MockObject
     */
    protected function getDoctrineRegistryMock($om)
    {
        $registryMock = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
                             ->setConstructorArgs([
                                 'registry',
                                 [],
                                 [],
                                 'default',
                                 'default',
                                 'proxy',
                             ])
                             ->getMock();

        $registryMock
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($om));

        return $registryMock;
    }

    /**
     * EntityManager mock object together with annotation mapping driver and
     * pdo_sqlite database in memory
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager($mockCustomHydrator = false)
    {
        $cache = new ArrayCache();

        // xml driver
        $xmlDriver = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver(array(
            __DIR__.'/../../Resources/config/model'    => 'Lexik\Bundle\TranslationBundle\Model',
            __DIR__.'/../../Resources/config/doctrine' => 'Lexik\Bundle\TranslationBundle\Entity',
        ));

        $config = Setup::createAnnotationMetadataConfiguration([
            __DIR__ . '/../../Model',
            __DIR__ . '/../../Entity',
        ], false, null, null, false);

        $config->setMetadataDriverImpl($xmlDriver);
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $config->setDefaultRepositoryClassName('Doctrine\ORM\EntityRepository');

        if ($mockCustomHydrator) {
            $config->setCustomHydrationModes([
                'SingleColumnArrayHydrator' => 'Lexik\Bundle\TranslationBundle\Util\Doctrine\SingleColumnArrayHydrator',
            ]);
        }

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $em = EntityManager::create($conn, $config);

        return $em;
    }

    /**
     * Create a DocumentManager instance for tests.
     *
     * @return DocumentManager
     */
    protected function getMockMongoDbDocumentManager()
    {
        $prefixes = array(
            __DIR__.'/../../Resources/config/model'    => 'Lexik\Bundle\TranslationBundle\Model',
            __DIR__.'/../../Resources/config/doctrine' => 'Lexik\Bundle\TranslationBundle\Document',
        );
        $xmlDriver = new XmlDriver($prefixes);

        $cache = new ArrayCache();

        $config = new Configuration();
        $config->setMetadataCacheImpl($cache);
        $config->setMetadataDriverImpl($xmlDriver);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_FILE_NOT_EXISTS);
        $config->setClassMetadataFactoryName(ClassMetadataFactory::class);
        $config->setDefaultDB('lexik_translation_bundle_test');
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setHydratorNamespace('Doctrine\ODM\MongoDB\Hydrator');
        $config->setAutoGenerateHydratorClasses(true);
        $config->setDefaultCommitOptions([]);

        $server = getenv('MONGO_SERVER') ?: null;
        $driverOptions = [
            'typeMap' =>  ['root' => 'array', 'document' => 'array'],
        ];
        $conn = new Client($server, [], $driverOptions);

        $dm = DocumentManager::create($conn, $config);

        return $dm;
    }

    /**
     * @return ConnectionWrapper
     */
    protected function getMockPropelConnection()
    {
        if (!class_exists('Lexik\\Bundle\\TranslationBundle\\Propel\\Base\\File')) {
            // classes are built in-memory.
            $builder = new QuickBuilder();
            $builder->setSchema(file_get_contents(__DIR__ . '/../../Resources/config/propel/schema.xml'));
            $con = $builder->build(null, null, null, null, ['tablemap', 'object', 'query']);
        } else {
            // in memory-classes already exist, create connection and SQL manually
            $dsn = 'sqlite::memory:';
            $pdoConnection = new PdoConnection($dsn);
            $con = new ConnectionWrapper($pdoConnection);
            Propel::getServiceContainer()->setConnection('default', $con);

            $con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
            $builder = new QuickBuilder();
            $builder->setSchema(file_get_contents(__DIR__ . '/../../Resources/config/propel/schema.xml'));
            $builder->buildSQL($con);
        }

        return $con;
    }
}
