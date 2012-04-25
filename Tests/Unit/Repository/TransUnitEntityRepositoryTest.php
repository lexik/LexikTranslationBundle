<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * Unit test for TransUnit entity's repository class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitEntityRepositoryTest extends BaseUnitTestCase
{
    /**
     * @group orm
     */
    public function testGetAllDomainsByLocale()
    {
        $em = $this->loadDatabase();
        $repository = $em->getRepository('Lexik\Bundle\TranslationBundle\Entity\TransUnit');

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
        $em = $this->loadDatabase(true);
        $repository = $em->getRepository('Lexik\Bundle\TranslationBundle\Entity\TransUnit');

        $results = $repository->getAllDomains();
        $expected = array('messages', 'superTranslations');

        $this->assertSame($expected, $results);
    }

    /**
     * @group orm
     */
    public function testGetAllByLocaleAndDomain()
    {
        $em = $this->loadDatabase();
        $repository = $em->getRepository('Lexik\Bundle\TranslationBundle\Entity\TransUnit');

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
     */
    public function testCount()
    {
        $em = $this->loadDatabase(true);
        $repository = $em->getRepository('Lexik\Bundle\TranslationBundle\Entity\TransUnit');

        $this->assertEquals(3, $repository->count(array('fr', 'de', 'en'), array()));
        $this->assertEquals(3, $repository->count(array('fr', 'it'), array()));
        $this->assertEquals(3, $repository->count(array('fr', 'de'), array('_search' => false, 'key' => 'good')));
        $this->assertEquals(1, $repository->count(array('fr', 'de'), array('_search' => true, 'key' => 'good')));
        $this->assertEquals(1, $repository->count(array('en', 'de'), array('_search' => true, 'domain' => 'super')));
        $this->assertEquals(1, $repository->count(array('en', 'fr', 'de'), array('_search' => true, 'key' => 'hel', 'domain' => 'uper')));
        $this->assertEquals(2, $repository->count(array('en', 'de'), array('_search' => true, 'key' => 'say', 'domain' => 'ssa')));
    }

    /**
     * @group orm
     */
    public function testGetTransUnitList()
    {
        $em = $this->loadDatabase(true);
        $repository = $em->getRepository('Lexik\Bundle\TranslationBundle\Entity\TransUnit');

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

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess'));
        $expected = array(
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess', 'key' => 'oo'));
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

    protected function loadDatabase($withCustomHydrator = false)
    {
        $em = $this->getMockSqliteEntityManager($withCustomHydrator);
        $this->createSchema($em);
        $this->loadFixtures($em);

        return $em;
    }
}