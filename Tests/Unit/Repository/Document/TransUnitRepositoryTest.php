<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository\Document;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * Unit test for TransUnit document's repository class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitRepositoryTest extends BaseUnitTestCase
{
    /**
     * @group odm
     */
    public function testGetAllDomainsByLocale()
    {
        $dm = $this->loadDatabase();
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

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
     * @group odm
     */
    public function testGetAllDomains()
    {
        $dm = $this->loadDatabase(true);
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $results = $repository->getAllDomains();
        $expected = array('messages', 'superTranslations');

        $this->assertSame($expected, $results);
    }

    /**
     * @group odm
     */
    public function testGetAllByLocaleAndDomain()
    {
        $dm = $this->loadDatabase();
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $results = $repository->getAllByLocaleAndDomain('de', 'messages');
        $expected = array();
        $this->assertSameTransUnit($expected, $results);


        $results = $repository->getAllByLocaleAndDomain('de', 'superTranslations');
        $expected = array(
            array('key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(array('locale' => 'de', 'content' => 'heil'))),
        );
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('en', 'messages');
        $expected = array(
            array('key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(array('locale' => 'en', 'content' => 'goodbye'))),
            array('key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(array('locale' => 'en', 'content' => 'what the fuck !?!'))),
        );
        $this->assertSameTransUnit($expected, $results);
    }

    /**
     * @group odm
     */
    public function testCount()
    {
        $dm = $this->loadDatabase(true);
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $this->assertEquals(3, $repository->count(null, array()));
        $this->assertEquals(3, $repository->count(array('fr', 'de', 'en'), array()));
        $this->assertEquals(3, $repository->count(array('fr', 'it'), array()));
        $this->assertEquals(3, $repository->count(array('fr', 'de'), array('_search' => false, 'key' => 'good')));
        $this->assertEquals(1, $repository->count(array('fr', 'de'), array('_search' => true, 'key' => 'good')));
        $this->assertEquals(1, $repository->count(array('en', 'de'), array('_search' => true, 'domain' => 'super')));
        $this->assertEquals(1, $repository->count(array('en', 'fr', 'de'), array('_search' => true, 'key' => 'hel', 'domain' => 'uper')));
        $this->assertEquals(2, $repository->count(array('en', 'de'), array('_search' => true, 'key' => 'say', 'domain' => 'ssa')));
    }

    /**
     * @group odm
     */
    public function testGetTransUnitList()
    {
        $dm = $this->loadDatabase(true);
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'ASC'));
        $expected = array(
            array('key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
            array('key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'de', 'content' => 'heil'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
            array('key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess'));
        $expected = array(
            array('key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
            array('key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess', 'key' => 'oo'));
        $expected = array(
            array('key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'en'), 10, 1, array('sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'fr' => 'alu'));
        $expected = array(
            array('key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'en', 'content' => 'hello'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(array('fr', 'de', 'en'), 2, 1, array('sidx' => 'domain', 'sord' => 'ASC'));
        $expected = array(
            array('key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
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
            array('key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'de', 'content' => 'heil'),
                array('locale' => 'en', 'content' => 'hello'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
        );
        $this->assertSameTransUnit($expected, $result);
    }

    /**
     * @group odm
     */
    public function testGetTranslationsForFile()
    {
        $dm = $this->loadDatabase();
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $file = $dm->getRepository(self::DOCUMENT_FILE_CLASS)->findOneBy(array(
            'domain' => 'messages',
            'locale' => 'fr',
            'extention' => 'yml',
        ));
        $this->assertInstanceOf(self::DOCUMENT_FILE_CLASS, $file);

        $result = $repository->getTranslationsForFile($file, false);
        $expected = array(
            'key.say_goodbye' => 'au revoir',
            'key.say_wtf' => 'c\'est quoi ce bordel !?!',
        );
        $this->assertEquals($expected, $result);

        // update a translation and then get translations with onlyUpdated = true
        $now = new \DateTime('now');
        $now->modify('+2 days');

        $cursor = $dm->createQueryBuilder(self::DOCUMENT_TRANS_UNIT_CLASS)
            ->update()
            ->field('translations.0.updated_at')->set(new \MongoTimestamp($now->format('U')))
            ->field('translations.1.updated_at')->set(new \MongoTimestamp($now->format('U')))
            ->field('key')->equals('key.say_goodbye')
            ->field('domain')->equals('messages')
            ->getQuery()
            ->execute();

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
        $dm = $this->getMockMongoDbDocumentManager();
        $this->createSchema($dm);
        $this->loadFixtures($dm);

        return $dm;
    }
}
