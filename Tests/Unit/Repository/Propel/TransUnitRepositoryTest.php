<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository\Propel;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;
use Lexik\Bundle\TranslationBundle\Propel\TransUnitRepository;
use Lexik\Bundle\TranslationBundle\Propel\FileRepository;
use Lexik\Bundle\TranslationBundle\Propel\FileQuery;
use Lexik\Bundle\TranslationBundle\Propel\TranslationQuery;

/**
 * Unit test for TransUnit entity's repository class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepositoryTest extends BaseUnitTestCase
{
    /**
     * @group orm
     */
    public function testGetAllDomainsByLocale()
    {
        $con = $this->loadDatabase();
        $repository = new TransUnitRepository($con);

        $results = $repository->getAllDomainsByLocale();
        $expected = array(
            array('locale' => 'de', 'domain' => 'superTranslations'),
            array('locale' => 'en', 'domain' => 'messages'),
            array('locale' => 'en', 'domain' => 'superTranslations'),
            array('locale' => 'fr', 'domain' => 'messages'),
            array('locale' => 'fr', 'domain' => 'superTranslations'),
        );

        $this->assertSame($expected, $results);
    }

    /**
     * @group orm
     */
    public function testGetAllDomains()
    {
        $con = $this->loadDatabase();
        $repository = new TransUnitRepository($con);

        $results = $repository->getAllDomains();
        $expected = array('messages', 'superTranslations');

        $this->assertSame($expected, $results);
    }

    /**
     * @group orm
     */
    public function testGetAllByLocaleAndDomain()
    {
        $con = $this->loadDatabase();
        $repository = new TransUnitRepository($con);

        $results = $repository->getAllByLocaleAndDomain('de', 'messages');
        $expected = array();
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('de', 'superTranslations');
        $expected = array(
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(array('locale' => 'de', 'content' => 'heil'))),
        );
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('en', 'messages');
        $expected = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(array('locale' => 'en', 'content' => 'goodbye'))),
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(array('locale' => 'en', 'content' => 'what the fuck !?!'))),
        );
        $this->assertSameTransUnit($expected, $results);
    }

    /**
     * @group orm
     * @dataProvider countProvider
     */
    public function testCount($expectedCount, $arguments)
    {
        $con = $this->loadDatabase();
        $repository = new TransUnitRepository($con);

        $count = call_user_func_array(array($repository, 'count'), $arguments);

        $this->assertEquals($expectedCount, $count);
    }

    public function countProvider()
    {
        return array(
            array(3, array(array('fr', 'de', 'en'), array())),
            array(3, array(array('fr', 'it'), array())),
            array(3, array(array('fr', 'de'), array('_search' => false, '_key' => 'good'))),
            array(1, array(array('fr', 'de'), array('_search' => true, '_key' => 'good'))),
            array(1, array(array('en', 'de'), array('_search' => true, '_domain' => 'super'))),
            array(1, array(array('en', 'fr', 'de'), array('_search' => true, '_key' => 'hel', '_domain' => 'uper'))),
            array(2, array(array('en', 'de'), array('_search' => true, '_key' => 'say', '_domain' => 'ssa'))),
        );
    }

    /**
     * @group orm
     */
    public function testGetTransUnitList()
    {
        $con = $this->loadDatabase();
        $repository = new TransUnitRepository($con);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'ASC'));
        $expected = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'de', 'content' => 'heil'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, '_domain' => 'mess'));
        $expected = array(
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, '_domain' => 'mess', '_key' => 'oo'));
        $expected = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'en'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'fr' => 'alu'));
        $expected = array(
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'en', 'content' => 'hello'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de', 'en'), 2, 1, array('sidx' => 'domain', 'sord' => 'ASC'));
        $expected = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'en', 'content' => 'goodbye'),
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'en', 'content' => 'what the fuck !?!'),
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de', 'en'), 2, 2, array('sidx' => 'domain', 'sord' => 'ASC'));
        $expected = array(
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'de', 'content' => 'heil'),
                array('locale' => 'en', 'content' => 'hello'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);
    }

    /**
     * @group orm
     */
    public function testGetTranslationsForFile()
    {
        $con = $this->loadDatabase();
        $repository = new TransUnitRepository($con);

        $file = FileQuery::create()->findOneByArray(array(
            'Domain' => 'messages',
            'Locale' => 'fr',
            'Extention' => 'yml',
        ), $con);
        $this->assertInstanceOf(self::PROPEL_FILE_CLASS, $file);

        $result = $repository->getTranslationsForFile($file, false);
        $expected = array(
            'key.say_goodbye' => 'au revoir',
            'key.say_wtf' => 'c\'est quoi ce bordel !?!',
        );
        $this->assertEquals($expected, $result);

        // update a translation and then get translations with onlyUpdated = true
        $now = new \DateTime('now');
        $now->modify('+2 days');

        TranslationQuery::create()
            ->filterByLocale('fr')
            ->filterByContent('au revoir')
            ->update(array('UpdatedAt' => $now), $con, false)
        ;

        $result = $repository->getTranslationsForFile($file, true);
        $expected = array(
            'key.say_goodbye' => 'au revoir',
        );
        $this->assertEquals($expected, $result);
    }

    protected function assertSameTransUnit($expected, $result)
    {
        $this->assertEquals(count($expected), count($result));

        foreach ($expected as $i => $transUnit) {
            $this->assertEquals($transUnit['id'], $result[$i]['id']);
            $this->assertEquals($transUnit['key'], $result[$i]['key']);
            $this->assertEquals($transUnit['domain'], $result[$i]['domain']);

            $this->assertEquals(count($transUnit['translations']), count($result[$i]['translations']));

            foreach ($transUnit['translations'] as $j => $translation) {
                $this->assertEquals($translation['locale'], $result[$i]['translations'][$j]['locale']);
                $this->assertEquals($translation['content'], $result[$i]['translations'][$j]['content']);
            }
        }
    }

    protected function loadDatabase()
    {
        $con = $this->getMockPropelConnection();
        $this->loadPropelFixtures($con);

        return $con;
    }
}