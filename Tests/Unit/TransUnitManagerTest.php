<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit;

use Doctrine\ORM\UnitOfWork;
use Lexik\Bundle\TranslationBundle\Translation\TransUnitManager;

/**
 * Unit test for TransUnitManager.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitManagerTest extends BaseUnitTestCase
{
    private $em;

    public function setUp()
    {
        $this->em = $this->getMockSqliteEntityManager();
        $this->createSchema($this->em);
    }

    public function testCreate()
    {
        $manager = new TransUnitManager($this->em);

        $transUnit = $manager->create('chuck.norris', 'badass');
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('chuck.norris', $transUnit->getKey());

        $transUnit = $manager->create('rambo', 'badass', true);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals('badass', $transUnit->getDomain());
        $this->assertEquals('rambo', $transUnit->getKey());
    }

    public function testAddTranslation()
    {
        $manager = new TransUnitManager($this->em);
        $class = 'Lexik\Bundle\TranslationBundle\Entity\TransUnit';
        $transUnit = $manager->create('bwah', 'messages', true);

        $translation = $manager->addTranslation($transUnit, 'en', 'bwaaaAaAahhHHh');
        $this->assertInstanceOf($class, $translation->getTransUnit());
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertEquals('bwaaaAaAahhHHh', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());

        $translation = $manager->addTranslation($transUnit, 'en', 'blebleble');
        $this->assertEquals(1, $transUnit->getTranslations()->count());
        $this->assertNull($translation);

        $translation = $manager->addTranslation($transUnit, 'fr', 'bwoOoOohH');
        $this->assertInstanceOf($class, $translation->getTransUnit());
        $this->assertEquals(2, $transUnit->getTranslations()->count());
        $this->assertEquals('bwoOoOohH', $translation->getContent());
        $this->assertEquals('fr', $translation->getLocale());
    }

    public function testUpdateTranslation()
    {
        $manager = new TransUnitManager($this->em);
        $transUnit = $manager->create('bwah', 'messages', true);
        $manager->addTranslation($transUnit, 'en', 'hello');
        $manager->addTranslation($transUnit, 'fr', 'salut');

        $translation = $manager->updateTranslation($transUnit, 'en', 'Hiiii');
        $this->assertEquals('Hiiii', $translation->getContent());
        $this->assertEquals('en', $translation->getLocale());
        $this->assertEquals(2, $transUnit->getTranslations()->count());

        $translation = $manager->updateTranslation($transUnit, 'de', 'Hallo');
        $this->assertNull($translation);
        $this->assertEquals(2, $transUnit->getTranslations()->count());
    }

    public function testNewInstance()
    {
        $manager = new TransUnitManager($this->em);
        $transUnit = $manager->newInstance();
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals(0, $transUnit->getTranslations()->count());

        $transUnit = $manager->newInstance(array('fr', 'en'));
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->em->getUnitOfWork()->getEntityState($transUnit));
        $this->assertEquals('fr', $transUnit->getTranslations()->get(0)->getLocale());
        $this->assertEquals('en', $transUnit->getTranslations()->get(1)->getLocale());
    }
}