<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository\Propel;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;
use Lexik\Bundle\TranslationBundle\Propel\FileRepository;

/**
 * Unit test for File entity's repository class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepositoryTest extends BaseUnitTestCase
{
    /**
     * @group orm
     */
    public function testFindForLocalesAndDomains()
    {
        $con = $this->getMockPropelConnection();
        $this->loadPropelFixtures($con);

        $repository = new FileRepository($con);

        $result = $repository->findForLocalesAndDomains(['de'], []);
        $expected = ['Resources/translations/superTranslations.de.yml'];
        $this->assertEquals(1, count($result));
        $this->assertFilesPath($expected, $result);

        $result = $repository->findForLocalesAndDomains(['fr'], []);
        $expected = ['Resources/translations/superTranslations.fr.yml', 'Resources/translations/messages.fr.yml'];
        $this->assertEquals(2, count($result));
        $this->assertFilesPath($expected, $result);

        $result = $repository->findForLocalesAndDomains([], ['messages']);
        $expected = ['Resources/translations/messages.fr.yml', 'Resources/translations/messages.en.yml'];
        $this->assertEquals(2, count($result));

        $result = $repository->findForLocalesAndDomains(['en', 'de'], ['messages', 'superTranslations']);
        $expected = ['Resources/translations/superTranslations.en.yml', 'Resources/translations/superTranslations.de.yml', 'Resources/translations/messages.en.yml'];
        $this->assertEquals(3, count($result));
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
