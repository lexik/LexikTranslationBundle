<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Annotations\AnnotationReader;

use Lexik\Bundle\TranslationBundle\Tests\Fixtures\TransUnitData;

/**
 * Base unit test class providing functions to create a mock entity manger, load schema and fixtures.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class BaseUnitTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Create the database schema.
     *
     * @param EntityManager $em
     */
    public function createSchema(EntityManager $em)
    {
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->createSchema($metadatas);
    }

    /**
     * Load test fixtures.
     *
     * @param EntityManager $em
     */
    public function loadFixtures(EntityManager $em)
    {
        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);

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
        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $cache = new \Doctrine\Common\Cache\ArrayCache();

        $reader = new AnnotationReader($cache);
        $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        $mappingDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array(
            __DIR__.'/../../../../../../../vendor/doctrine/lib',
            __DIR__.'/../../Entity',
        ));

        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->any())
            ->method('getMetadataCacheImpl')
            ->will($this->returnValue($cache));
        $config->expects($this->any())
            ->method('getQueryCacheImpl')
            ->will($this->returnValue($cache));
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(sys_get_temp_dir()));
        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));
        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));
        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver));
        $config->expects($this->any())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\ORM\Mapping\ClassMetadataFactory'));

        if ($mockCustomHydrator) {
            $config->expects($this->any())
                ->method('getCustomHydrationMode')
                ->with($this->equalTo('SingleColumnArrayHydrator'))
                ->will($this->returnValue('Lexik\Bundle\TranslationBundle\Hydrators\SingleColumnArrayHydrator'));
        }

        $em = EntityManager::create($conn, $config);

        return $em;
    }
}