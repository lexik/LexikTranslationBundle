<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Importer;

use Lexik\Bundle\TranslationBundle\Translation\Importer\FileImporter;
use Lexik\Bundle\TranslationBundle\Manager\FileManager;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManager;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Finder\SplFileInfo;

/**
 * FileImporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileImporterTest extends BaseUnitTestCase
{
    /**
     * @group importer
     */
    public function testImport()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);

        $loaders = array(
            'yml' => new YamlFileLoader(),
            'php' => new PhpFileLoader(),
        );

        $storage = $this->getORMStorage($em);

        $fileManager = new FileManager($storage, self::ENTITY_FILE_CLASS, '/test/root/dir/app');
        $transUnitManager = new TransUnitManager($storage, $fileManager, '/test/root/dir/app');

        $importer = new FileImporter($loaders, $storage, $transUnitManager, $fileManager);

        $this->assertDatabaseEntries($em, 0);

        // import files
        $files = array(
            new SplFileInfo(__DIR__.'/../../../Fixtures/test.en.yml', '', ''),
            new SplFileInfo(__DIR__.'/../../../Fixtures/test.fr.php', '', ''),
        );

        foreach ($files as $file) {
            $importer->import($file);
        }

        $this->assertDatabaseEntries($em, 2);
    }

    /**
     * Counts the number of entries in each tables.
     *
     * @param EntityManager $em
     * @param int $expected
     */
    protected function assertDatabaseEntries($em, $expected)
    {
        $number = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS)
            ->createQueryBuilder('tu')
            ->select('COUNT(tu.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($expected, $number);

        $number = $em->getRepository(self::ENTITY_TRANSLATION_CLASS)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($expected * 2, $number);

        $number = $em->getRepository(self::ENTITY_FILE_CLASS)
            ->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($expected, $number);
    }
}