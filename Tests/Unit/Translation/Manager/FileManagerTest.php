<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Manager;

use Doctrine\ODM\MongoDB\UnitOfWork as ODMUnitOfWork;
use Doctrine\ORM\UnitOfWork as ORMUnitOfWork;

use Lexik\Bundle\TranslationBundle\Manager\FileManager;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * Unit test for FileManager.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileManagerTest extends BaseUnitTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
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

    public function setUp()
    {
        $this->em = $this->getMockSqliteEntityManager();
        $this->createSchema($this->em);
        $this->loadFixtures($this->em);

        $this->ormStorage = $this->getORMStorage($this->em);

        $this->dm = $this->getMockMongoDbDocumentManager();
        $this->createSchema($this->dm);
        $this->loadFixtures($this->dm);

        $this->odmStorage = $this->getMongoDBStorage($this->dm);
    }

    /**
     * @group orm
     */
    public function testORMCreate()
    {
        $manager = new FileManager($this->ormStorage, '/test/root/dir/app');

        $file = $manager->create('myDomain.en.yml', '/test/root/dir/src/Project/CoolBundle/Resources/translations');
        $this->assertEquals(ORMUnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($file));
        $this->assertEquals('myDomain', $file->getDomain());
        $this->assertEquals('en', $file->getLocale());
        $this->assertEquals('yml', $file->getExtention());
        $this->assertEquals('myDomain.en.yml', $file->getName());
        $this->assertEquals('../src/Project/CoolBundle/Resources/translations', $file->getPath());

        $file = $manager->create('messages.fr.xliff', '/test/root/dir/app/Resources/translations', true);
        $this->assertEquals(ORMUnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($file));
        $this->assertEquals('messages', $file->getDomain());
        $this->assertEquals('fr', $file->getLocale());
        $this->assertEquals('xliff', $file->getExtention());
        $this->assertEquals('messages.fr.xliff', $file->getName());
        $this->assertEquals('Resources/translations', $file->getPath());
    }

    /**
     * @group odm
     */
    public function testODMCreate()
    {
        $manager = new FileManager($this->odmStorage, '/test/root/dir/app');

        $file = $manager->create('myDomain.en.yml', '/test/root/dir/src/Project/CoolBundle/Resources/translations');
        $this->assertEquals(ODMUnitOfWork::STATE_MANAGED, $this->dm->getUnitOfWork()->getDocumentState($file));
        $this->assertEquals('myDomain', $file->getDomain());
        $this->assertEquals('en', $file->getLocale());
        $this->assertEquals('yml', $file->getExtention());
        $this->assertEquals('myDomain.en.yml', $file->getName());
        $this->assertEquals('../src/Project/CoolBundle/Resources/translations', $file->getPath());

        $file = $manager->create('messages.fr.xliff', '/test/root/dir/app/Resources/translations', true);
        $this->assertEquals(ODMUnitOfWork::STATE_MANAGED, $this->dm->getUnitOfWork()->getDocumentState($file));
        $this->assertEquals('messages', $file->getDomain());
        $this->assertEquals('fr', $file->getLocale());
        $this->assertEquals('xliff', $file->getExtention());
        $this->assertEquals('messages.fr.xliff', $file->getName());
        $this->assertEquals('Resources/translations', $file->getPath());
    }

    /**
     * @group orm
     */
    public function testORMGetFor()
    {
        $repository = $this->em->getRepository(self::ENTITY_FILE_CLASS);
        $manager = new FileManager($this->ormStorage, '/test/root/dir/app');

        $total = count($repository->findAll());
        $this->assertEquals(5, $total);

        // get an existing file
        $file = $manager->getFor('superTranslations.de.yml', '/test/root/dir/app/Resources/translations');
        $this->em->flush();

        $total = count($repository->findAll());
        $this->assertEquals(5, $total);

        // get a new file
        $file = $manager->getFor('superTranslations.it.yml', '/test/root/dir/app/Resources/translations');
        $this->em->flush();

        $total = count($repository->findAll());
        $this->assertEquals(6, $total);
    }

    /**
     * @group odm
     */
    public function testODMGetFor()
    {
        $repository = $this->dm->getRepository(self::DOCUMENT_FILE_CLASS);
        $manager = new FileManager($this->odmStorage, '/test/root/dir/app');

        $total = count($repository->findAll());
        $this->assertEquals(5, $total);

        // get an existing file
        $file = $manager->getFor('superTranslations.de.yml', '/test/root/dir/app/Resources/translations');
        $this->dm->flush();

        $total = count($repository->findAll());
        $this->assertEquals(5, $total);

        // get a new file
        $file = $manager->getFor('superTranslations.it.yml', '/test/root/dir/app/Resources/translations');
        $this->dm->flush();

        $total = count($repository->findAll());
        $this->assertEquals(6, $total);
    }
}
