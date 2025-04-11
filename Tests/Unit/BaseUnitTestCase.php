<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit;

use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedXmlDriver as XmlDriver;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory as DoctrineClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\ORMSetup as Setup;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\TranslationBundle\Storage\DoctrineMongoDBStorage;
use Lexik\Bundle\TranslationBundle\Storage\DoctrineORMStorage;
use Lexik\Bundle\TranslationBundle\Tests\Fixtures\TransUnitData;
use Lexik\Bundle\TranslationBundle\Util\Doctrine\SingleColumnArrayHydrator;
use MongoDB\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Base unit test class providing functions to create a mock entity manger, load schema and fixtures.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class BaseUnitTestCase extends TestCase
{
    final const ENTITY_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Entity\TransUnit';
    final const ENTITY_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Entity\Translation';
    final const ENTITY_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Entity\File';

    final const DOCUMENT_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Document\TransUnit';
    final const DOCUMENT_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Document\Translation';
    final const DOCUMENT_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Document\File';

    /**
     * Create a storage class form doctrine ORM.
     *
     * @return DoctrineORMStorage
     */
    protected function getORMStorage(EntityManager $em)
    {
        $registryMock = $this->getDoctrineRegistryMock($em);

        $storage = new DoctrineORMStorage($registryMock, 'default', [
            'trans_unit'  => self::ENTITY_TRANS_UNIT_CLASS,
            'translation' => self::ENTITY_TRANSLATION_CLASS,
            'file'        => self::ENTITY_FILE_CLASS,
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
            'trans_unit'  => self::DOCUMENT_TRANS_UNIT_CLASS,
            'translation' => self::DOCUMENT_TRANSLATION_CLASS,
            'file'        => self::DOCUMENT_FILE_CLASS,
        ]);

        return $storage;
    }

    /**
     * Create the database schema.
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
     */
    protected function loadFixtures(ObjectManager $om)
    {
        $executor = null;
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
     * @param $om
     * @return MockObject
     */
    protected function getDoctrineRegistryMock($om)
    {
        $registryMock = $this->getMockBuilder(ManagerRegistry::class)
                             ->setConstructorArgs(
                                 [
                                     'registry',
                                     [],
                                     [],
                                     'default',
                                     'default',
                                     'proxy',
                                 ]
                             )
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
        $cache = new ArrayAdapter();

        // xml driver
        $xmlDriver = new SimplifiedXmlDriver(
            [
                __DIR__ . '/../../Resources/config/model'    => 'Lexik\Bundle\TranslationBundle\Model',
                __DIR__ . '/../../Resources/config/doctrine' => 'Lexik\Bundle\TranslationBundle\Entity',
            ]
        );

        $config = Setup::createAttributeMetadataConfiguration(
            [
                __DIR__ . '/../../Model',
                __DIR__ . '/../../Entity',
            ], false, null, null
        );

        $config->setMetadataDriverImpl($xmlDriver);
        $config->setMetadataCache($cache);
        $config->setQueryCache($cache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName(DoctrineClassMetadataFactory::class);
        $config->setDefaultRepositoryClassName(EntityRepository::class);

        if ($mockCustomHydrator) {
            $config->setCustomHydrationModes(
                [
                    'SingleColumnArrayHydrator' => SingleColumnArrayHydrator::class,
                ]
            );
        }

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $connection = DriverManager::getConnection($conn);
        $em = new EntityManager($connection, $config);

        return $em;
    }

    /**
     * Create a DocumentManager instance for tests.
     *
     * @return DocumentManager
     * @throws MongoDBException
     */
    protected function getMockMongoDbDocumentManager(): DocumentManager
    {
        $prefixes = [
            __DIR__ . '/../../Resources/config/model'    => 'Lexik\Bundle\TranslationBundle\Model',
            __DIR__ . '/../../Resources/config/doctrine' => 'Lexik\Bundle\TranslationBundle\Document',
        ];
        $xmlDriver = new XmlDriver($prefixes);

        $cache = new ArrayAdapter();

        $config = new Configuration();
        $config->setMetadataCache($cache);
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

        $server = MONGO_SERVER;
        $driverOptions = [
            'typeMap' => [
                'root'     => 'array',
                'document' => 'array',
            ],
        ];
        $conn = new Client($server, [], $driverOptions);

        $dm = DocumentManager::create($conn, $config);


        return $dm;
    }
}
