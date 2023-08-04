<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository\Document;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * Unit test for File document's repository class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepositoryTest extends BaseUnitTestCase
{
    /**
     * @group odm
     */
    public function testFindForLocalesAndDomains()
    {
        $dm = $this->getMockMongoDbDocumentManager();
        $this->createSchema($dm);
        $this->loadFixtures($dm);

        $repository = $dm->getRepository(self::DOCUMENT_FILE_CLASS);

        $result = $repository->findForLocalesAndDomains(['de'], []);
        $expected = ['Resources/translations/superTranslations.de.yml'];
        $this->assertEquals(1, is_countable($result) ? count($result) : 0);
        $this->assertFilesPath($expected, $result);

        $result = $repository->findForLocalesAndDomains(['fr'], []);
        $expected = ['Resources/translations/superTranslations.fr.yml', 'Resources/translations/messages.fr.yml'];
        $this->assertEquals(2, is_countable($result) ? count($result) : 0);
        $this->assertFilesPath($expected, $result);

        $result = $repository->findForLocalesAndDomains([], ['messages']);
        $expected = ['Resources/translations/messages.fr.yml', 'Resources/translations/messages.en.yml'];
        $this->assertEquals(2, is_countable($result) ? count($result) : 0);

        $result = $repository->findForLocalesAndDomains(['en', 'de'], ['messages', 'superTranslations']);
        $expected = ['Resources/translations/superTranslations.en.yml', 'Resources/translations/superTranslations.de.yml', 'Resources/translations/messages.en.yml'];
        $this->assertEquals(3, is_countable($result) ? count($result) : 0);
        $this->assertFilesPath($expected, $result);
    }

    /**
     * Check files path.
     *
     * @param array $expected
     * @param array $result
     */
    public function assertFilesPath($expected, $result)
    {
        $i = 0;
        foreach ($result as $file) {
            $this->assertEquals($expected[$i], $file->getPath().'/'.$file->getName());
            $i++;
        }
    }
}
