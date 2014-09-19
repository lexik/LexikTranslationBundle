<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Manager;;

use Doctrine\ODM\MongoDB\UnitOfWork as ODMUnitOfWork;
use Doctrine\ORM\UnitOfWork as ORMUnitOfWork;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitManager;
use Lexik\Bundle\TranslationBundle\Manager\FileManager;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * Unit test for TransUnitManager.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitManagerTest extends BaseUnitTestCase
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * @var \Lexik\Bundle\TranslationBundle\Storage\DoctrineORMStorage
     */
    private $ormStorage;

    /**
     * @var \Lexik\Bundle\TranslationBundle\Storage\DoctrineMongoDBStorage
     */
    private $odmStorage;

    /**
     *
     * @var Lexik\Bundle\TranslationBundle\Storage\PropelStorage
     */
    private $propelStorage;

    /**
     * @var string
     */
    private $rootDir = '/test/root/dir/app';

    public function setUp()
    {
        $this->em = $this->getMockSqliteEntityManager();
        $this->createSchema($this->em);

        $this->ormStorage = $this->getORMStorage($this->em);

        $this->dm = $this->getMockMongoDbDocumentManager();
        $this->createSchema($this->dm);

        $this->odmStorage = $this->getMongoDBStorage($this->dm);

        $this->getMockPropelConnection();
        $this->propelStorage = $this->getPropelStorage();
    }

    /**
     * @group orm
     */
    public function testORMCreate()
    {
        $fileManager = new FileManager($this->ormStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->ormStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('chuck.norris', 'badass');
        $this->assertEquals(ORMUnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('chuck.norris', $transUnit->getKey());

        $transUnit = $manager->create('rambo', 'badass', true);
        $this->assertEquals(ORMUnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('rambo', $transUnit->getKey());
    }

    /**
     * @group odm
     */
    public function testODMCreate()
    {
        $fileManager = new FileManager($this->odmStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->odmStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('chuck.norris', 'badass');
        $this->assertEquals(ODMUnitOfWork::STATE_MANAGED, $this->dm->getUnitOfWork()->getDocumentState($transUnit));
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('chuck.norris', $transUnit->getKey());

        $transUnit = $manager->create('rambo', 'badass', true);
        $this->assertEquals(ODMUnitOfWork::STATE_MANAGED, $this->dm->getUnitOfWork()->getDocumentState($transUnit));
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('rambo', $transUnit->getKey());
    }

    /**
     * @group propel
     */
    public function testPropelCreate()
    {
        $fileManager = new FileManager($this->propelStorage, self::PROPEL_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->propelStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('chuck.norris', 'badass');
        $this->assertTrue($transUnit->isNew());
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('chuck.norris', $transUnit->getKey());

        $transUnit = $manager->create('rambo', 'badass', true);
        $this->assertFalse($transUnit->isNew());
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('rambo', $transUnit->getKey());
    }

    /**
     * @group orm
     */
    public function testORMAddTranslation()
    {
        $fileManager = new FileManager($this->ormStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->ormStorage, $fileManager, $this->rootDir);

        $class = 'Lexik\Bundle\TranslationBundle\Entity\TransUnit';
        $transUnit = $manager->create('bwah', 'messages', true);

        $translation = $manager->addTranslation($transUnit, 'en', 'bwaaaAaAahhHHh', null, true);
        $this->assertInstanceOf($class, $translation->getTransUnit());
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertEquals('bwaaaAaAahhHHh', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());

        $translation = $manager->addTranslation($transUnit, 'en', 'blebleble', null, true);
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertNull($translation);

        $translation = $manager->addTranslation($transUnit, 'fr', 'bwoOoOohH', null, true);
        $this->assertInstanceOf($class, $translation->getTransUnit());
        $this->assertEquals(2, $transUnit->getTranslations()->count());
        $this->assertEquals('bwoOoOohH', $translation->getContent());
        $this->assertEquals('fr', $translation->getLocale());
    }

    /**
     * @group odm
     */
    public function testODMAddTranslation()
    {
        $fileManager = new FileManager($this->odmStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->odmStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('bwah', 'messages', true);

        $translation = $manager->addTranslation($transUnit, 'en', 'bwaaaAaAahhHHh', null, true);
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertEquals('bwaaaAaAahhHHh', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());

        $translation = $manager->addTranslation($transUnit, 'en', 'blebleble', null, true);
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertNull($translation);

        $translation = $manager->addTranslation($transUnit, 'fr', 'bwoOoOohH', null, true);
        $this->assertEquals(2, $transUnit->getTranslations()->count());
        $this->assertEquals('bwoOoOohH', $translation->getContent());
        $this->assertEquals('fr', $translation->getLocale());
    }

    /**
     * @group propel
     */
    public function testPropelAddTranslation()
    {
        $fileManager = new FileManager($this->propelStorage, self::PROPEL_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->propelStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('bwah', 'messages', true);

        $translation = $manager->addTranslation($transUnit, 'en', 'bwaaaAaAahhHHh', null, true);
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertEquals('bwaaaAaAahhHHh', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());

        $translation = $manager->addTranslation($transUnit, 'en', 'blebleble', null, true);
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertNull($translation);

        $translation = $manager->addTranslation($transUnit, 'fr', 'bwoOoOohH', null, true);
        $this->assertEquals(2, $transUnit->getTranslations()->count());
        $this->assertEquals('bwoOoOohH', $translation->getContent());
        $this->assertEquals('fr', $translation->getLocale());
    }

    /**
     * @group orm
     */
    public function testORMUpdateTranslation()
    {
        $fileManager = new FileManager($this->ormStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->ormStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('bwah', 'messages', true);
        $manager->addTranslation($transUnit, 'en', 'hello');
        $manager->addTranslation($transUnit, 'fr', 'salut');

        $translation = $manager->updateTranslation($transUnit, 'en', 'Hiiii', true);
        $this->assertEquals('Hiiii', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());
        $this->assertEquals(2, $transUnit->getTranslations()->count());

        $translation = $manager->updateTranslation($transUnit, 'de', 'Hallo', true);
        $this->assertNull($translation);
        $this->assertEquals(2, $transUnit->getTranslations()->count());
    }

    /**
     * @group odm
     */
    public function testODMUpdateTranslation()
    {
        $fileManager = new FileManager($this->odmStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->odmStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('bwah', 'messages', true);
        $manager->addTranslation($transUnit, 'en', 'hello');
        $manager->addTranslation($transUnit, 'fr', 'salut');

        $translation = $manager->updateTranslation($transUnit, 'en', 'Hiiii', true);
        $this->assertEquals('Hiiii', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());
        $this->assertEquals(2, $transUnit->getTranslations()->count());

        $translation = $manager->updateTranslation($transUnit, 'de', 'Hallo', true);
        $this->assertNull($translation);
        $this->assertEquals(2, $transUnit->getTranslations()->count());
    }

    /**
     * @group propel
     */
    public function testPropelUpdateTranslation()
    {
        $fileManager = new FileManager($this->propelStorage, self::PROPEL_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->propelStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->create('bwah', 'messages', true);
        $manager->addTranslation($transUnit, 'en', 'hello');
        $manager->addTranslation($transUnit, 'fr', 'salut');

        $translation = $manager->updateTranslation($transUnit, 'en', 'Hiiii', true);
        $this->assertEquals('Hiiii', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());
        $this->assertEquals(2, $transUnit->getTranslations()->count());

        $translation = $manager->updateTranslation($transUnit, 'de', 'Hallo', true);
        $this->assertNull($translation);
        $this->assertEquals(2, $transUnit->getTranslations()->count());
    }

    /**
     * @group orm
     */
    public function testORMNewInstance()
    {
        $fileManager = new FileManager($this->ormStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->ormStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->newInstance();
        $this->assertEquals(ORMUnitOfWork::STATE_NEW, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals(0, $transUnit->getTranslations()->count());

        $transUnit = $manager->newInstance(array('fr', 'en'));
        $this->assertEquals(ORMUnitOfWork::STATE_NEW, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals('fr', $transUnit->getTranslations()->get(0)->getLocale());
        $this->assertEquals('en', $transUnit->getTranslations()->get(1)->getLocale());
    }

    /**
     * @group odm
     */
    public function testODMNewInstance()
    {
        $fileManager = new FileManager($this->odmStorage, self::ENTITY_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->odmStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->newInstance();
        $this->assertEquals(ORMUnitOfWork::STATE_NEW, $this->dm->getUnitOfWork()->getDocumentState($transUnit));
        $this->assertEquals(0, $transUnit->getTranslations()->count());

        $transUnit = $manager->newInstance(array('fr', 'en'));
        $this->assertEquals(ORMUnitOfWork::STATE_NEW, $this->dm->getUnitOfWork()->getDocumentState($transUnit));
        $this->assertEquals('fr', $transUnit->getTranslations()->get(0)->getLocale());
        $this->assertEquals('en', $transUnit->getTranslations()->get(1)->getLocale());
    }

    /**
     * @group propel
     */
    public function testPropelNewInstance()
    {
        $fileManager = new FileManager($this->propelStorage, self::PROPEL_FILE_CLASS, $this->rootDir);
        $manager = new TransUnitManager($this->propelStorage, $fileManager, $this->rootDir);

        $transUnit = $manager->newInstance();
        $this->assertTrue($transUnit->isNew());
        $this->assertEquals(0, $transUnit->getTranslations()->count());

        $transUnit = $manager->newInstance(array('fr', 'en'));
        $this->assertTrue($transUnit->isNew());
        $this->assertEquals('fr', $transUnit->getTranslations()->get(0)->getLocale());
        $this->assertEquals('en', $transUnit->getTranslations()->get(1)->getLocale());
    }
}
