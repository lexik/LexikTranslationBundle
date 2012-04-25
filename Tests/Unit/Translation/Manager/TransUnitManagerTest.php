<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Manager;;

use Doctrine\ODM\MongoDB\UnitOfWork as ODMUnitOfWork;
use Doctrine\ORM\UnitOfWork as ORMUnitOfWork;

use Lexik\Bundle\TranslationBundle\Translation\Manager\TransUnitManager;

/**
 * Unit test for TransUnitManager.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitManagerTest extends BaseUnitTestCase
{
    const ENTITY_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Entity\TransUnit';
    const ENTITY_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Entity\Translation';

    const DOCUMENT_TRANS_UNIT_CLASS  = 'Lexik\Bundle\TranslationBundle\Document\TransUnit';
    const DOCUMENT_TRANSLATION_CLASS = 'Lexik\Bundle\TranslationBundle\Document\Translation';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->em = $this->getMockSqliteEntityManager();
        $this->createSchema($this->em);

        //$this->dm = $this->getMockMongoDbDocumentManager();
        //$this->createSchema($this->dm);
    }

    /**
     * @group orm
     */
    public function testORMCreate()
    {
        $manager = new TransUnitManager($this->em, self::ENTITY_TRANS_UNIT_CLASS, self::ENTITY_TRANSLATION_CLASS);

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
        $manager = new TransUnitManager($this->dm, self::DOCUMENT_TRANS_UNIT_CLASS, self::DOCUMENT_TRANSLATION_CLASS);

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
     * @group orm
     */
    public function testORMAddTranslation()
    {
        $manager = new TransUnitManager($this->em, self::ENTITY_TRANS_UNIT_CLASS, self::ENTITY_TRANSLATION_CLASS);

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
        $manager = new TransUnitManager($this->dm, self::DOCUMENT_TRANS_UNIT_CLASS, self::DOCUMENT_TRANSLATION_CLASS);

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
        $manager = new TransUnitManager($this->em, self::ENTITY_TRANS_UNIT_CLASS, self::ENTITY_TRANSLATION_CLASS);

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
        $manager = new TransUnitManager($this->dm, self::DOCUMENT_TRANS_UNIT_CLASS, self::DOCUMENT_TRANSLATION_CLASS);

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
        $manager = new TransUnitManager($this->em, self::ENTITY_TRANS_UNIT_CLASS, self::ENTITY_TRANSLATION_CLASS);

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
        $manager = new TransUnitManager($this->dm, self::DOCUMENT_TRANS_UNIT_CLASS, self::DOCUMENT_TRANSLATION_CLASS);

        $transUnit = $manager->newInstance();
        $this->assertEquals(ORMUnitOfWork::STATE_NEW, $this->dm->getUnitOfWork()->getDocumentState($transUnit));
        $this->assertEquals(0, $transUnit->getTranslations()->count());

        $transUnit = $manager->newInstance(array('fr', 'en'));
        $this->assertEquals(ORMUnitOfWork::STATE_NEW, $this->dm->getUnitOfWork()->getDocumentState($transUnit));
        $this->assertEquals('fr', $transUnit->getTranslations()->get(0)->getLocale());
        $this->assertEquals('en', $transUnit->getTranslations()->get(1)->getLocale());
    }
}