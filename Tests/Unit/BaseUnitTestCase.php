<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Tools\Setup;

use Lexik\Bundle\TranslationBundle\Storage\DoctrineMongoDBStorage;
use Lexik\Bundle\TranslationBundle\Storage\DoctrineORMStorage;
use Lexik\Bundle\TranslationBundle\Tests\Fixtures\TransUnitData;

/**
 * Base unit test class providing functions to create a mock entity manger, load schema and fixtures.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class BaseUnitTestCase extends \PHPUnit_Framework_TestCase
{
    const ENTITY_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Entity\TransUnit';
    const ENTITY_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Entity\Translation';
    const ENTITY_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Entity\File';

    const DOCUMENT_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Document\TransUnit';
    const DOCUMENT_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Document\Translation';
    const DOCUMENT_FILE_CLASS        = 'Lexik\Bundle\TranslationBundle\Document\File';

    /**
     * Create astorage class form doctrine ORM.
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @return \Lexik\Bundle\TranslationBundle\Storage\DoctrineORMStorage
     */
    protected function getORMStorage(\Doctrine\ORM\EntityManager $em)
    {
        $storage = new DoctrineORMStorage($em, array(
            'trans_unit'  => self::ENTITY_TRANS_UNIT_CLASS,
            'translation' => self::ENTITY_TRANSLATION_CLASS,
            'file'        => self::ENTITY_FILE_CLASS,
        ));

        return $storage;
    }

    /**
     * Create astorage class form doctrine Mongo DB.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @return \Lexik\Bundle\TranslationBundle\Storage\DoctrineORMStorage
     */
    protected function getMongoDBStorage(\Doctrine\ODM\MongoDB\DocumentManager $dm)
    {
        $storage = new DoctrineMongoDBStorage($dm, array(
            'trans_unit'  => self::DOCUMENT_TRANS_UNIT_CLASS,
            'translation' => self::DOCUMENT_TRANSLATION_CLASS,
            'file'        => self::DOCUMENT_FILE_CLASS,
        ));

        return $storage;
    }

    /**
     * Create the database schema.
     *
     * @param ObjectManager $om
     */
    protected function createSchema(ObjectManager $om)
    {
        if ($om instanceof \Doctrine\ORM\EntityManager) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($om);
            $schemaTool->createSchema($om->getMetadataFactory()->getAllMetadata());
        } else if ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $sm = new \Doctrine\ODM\MongoDB\SchemaManager($om, $om->getMetadataFactory());
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
        if ($om instanceof \Doctrine\ORM\EntityManager) {
            $purger = new ORMPurger();
            $executor = new ORMExecutor($om, $purger);
        } else if ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $purger = new MongoDBPurger();
            $executor = new MongoDBExecutor($om, $purger);
        }

        $fixtures = new TransUnitData();
        $executor->execute(array($fixtures), false);
    }

    /**
     * EntityManager mock object together with annotation mapping driver and
     * pdo_sqlite database in memory
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager($mockCustomHydrator = false)
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();

        // xml driver
        $xmlDriver = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver(array(
            __DIR__.'/../../Resources/config/doctrine' => 'Lexik\Bundle\TranslationBundle\Entity',
        ));

        $config = Setup::createAnnotationMetadataConfiguration(array(
                __DIR__.'/../../Entity',
        ), false, null, null, false);

        $config->setMetadataDriverImpl($xmlDriver);
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $config->setDefaultRepositoryClassName('Doctrine\ORM\EntityRepository');

        if ($mockCustomHydrator) {
            $config->setCustomHydrationModes(array(
                'SingleColumnArrayHydrator' => 'Lexik\Bundle\TranslationBundle\Hydrators\SingleColumnArrayHydrator',
            ));
        }

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $em = \Doctrine\ORM\EntityManager::create($conn, $config);

        return $em;
    }

    /**
     * Create a DocumentManager instance for tests.
     *
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getMockMongoDbDocumentManager()
    {
        $prefixes = array(
            __DIR__.'/../../Resources/config/doctrine' => 'Lexik\Bundle\TranslationBundle\Document',
        );
        $xmlDriver = new \Doctrine\Bundle\MongoDBBundle\Mapping\Driver\XmlDriver($prefixes);

        $cache = new \Doctrine\Common\Cache\ArrayCache();

        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setMetadataCacheImpl($cache);
        $config->setMetadataDriverImpl($xmlDriver);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName('Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory');
        $config->setDefaultDB('lexik_translation_bundle_test');
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setHydratorNamespace('Doctrine\ODM\MongoDB\Hydrator');
        $config->setAutoGenerateHydratorClasses(true);
        $config->setDefaultCommitOptions(array());

        $options = array(
            'connect'  => true,
            'username' => 'travis',
            'password' => 'lexik',
        );
        $conn = new \Doctrine\MongoDB\Connection(null, $options, $config);

        $dm = \Doctrine\ODM\MongoDB\DocumentManager::create($conn, $config);

        return $dm;
    }
}
