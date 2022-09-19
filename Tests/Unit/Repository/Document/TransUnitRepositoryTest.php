<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository\Document;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;
use MongoDB\BSON\Timestamp;

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

        $expected = [
            ['locale' => 'de', 'domain' => 'superTranslations'],
            ['locale' => 'en', 'domain' => 'messages'],
            ['locale' => 'en', 'domain' => 'superTranslations'],
            ['locale' => 'fr', 'domain' => 'messages'],
            ['locale' => 'fr', 'domain' => 'superTranslations'],
        ];

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
        $expected = ['messages', 'superTranslations'];

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
        $expected = [];
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('de', 'superTranslations');
        $expected = [
            [
                'key' => 'key.say_hello',
                'domain' => 'superTranslations',
                'translations' => [['locale' => 'de', 'content' => 'heil']],
            ],
        ];
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('en', 'messages');
        $expected = [
            [
                'key' => 'key.say_goodbye',
                'domain' => 'messages',
                'translations' => [['locale' => 'en', 'content' => 'goodbye']],
            ],
            [
                'key' => 'key.say_wtf',
                'domain' => 'messages',
                'translations' => [['locale' => 'en', 'content' => 'what the fuck !?!']],
            ],
        ];
        $this->assertSameTransUnit($expected, $results);
    }

    /**
     * @group odm
     */
    public function testCount()
    {
        $dm = $this->loadDatabase(true);
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $this->assertEquals(3, $repository->count(null, []));
        $this->assertEquals(3, $repository->count(['fr', 'de', 'en'], []));
        $this->assertEquals(3, $repository->count(['fr', 'it'], []));
        $this->assertEquals(3, $repository->count(['fr', 'de'], ['_search' => false, 'key' => 'good']));
        $this->assertEquals(1, $repository->count(['fr', 'de'], ['_search' => true, 'key' => 'good']));
        $this->assertEquals(1, $repository->count(['en', 'de'], ['_search' => true, 'domain' => 'super']));
        $this->assertEquals(1,
            $repository->count(['en', 'fr', 'de'], ['_search' => true, 'key' => 'hel', 'domain' => 'uper']));
        $this->assertEquals(2,
            $repository->count(['en', 'de'], ['_search' => true, 'key' => 'say', 'domain' => 'ssa']));
    }

    /**
     * @group odm
     */
    public function testGetTransUnitList()
    {
        $dm = $this->loadDatabase(true);
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $result = $repository->getTransUnitList(['fr', 'de'], 10, 1, ['sidx' => 'key', 'sord' => 'ASC']);
        $expected = [
            [
                'key' => 'key.say_goodbye',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'fr', 'content' => 'au revoir'],
                ],
            ],
            [
                'key' => 'key.say_hello',
                'domain' => 'superTranslations',
                'translations' => [
                    ['locale' => 'de', 'content' => 'heil'],
                    ['locale' => 'fr', 'content' => 'salut'],
                ],
            ],
            [
                'key' => 'key.say_wtf',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'],
                ],
            ],
        ];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de'], 10, 1,
            ['sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess']);
        $expected = [
            [
                'key' => 'key.say_wtf',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'],
                ],
            ],
            [
                'key' => 'key.say_goodbye',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'fr', 'content' => 'au revoir'],
                ],
            ],
        ];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de'], 10, 1,
            ['sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess', 'key' => 'oo']);
        $expected = [
            [
                'key' => 'key.say_goodbye',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'fr', 'content' => 'au revoir'],
                ],
            ],
        ];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'en'], 10, 1,
            ['sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'fr' => 'alu']);
        $expected = [
            [
                'key' => 'key.say_hello',
                'domain' => 'superTranslations',
                'translations' => [
                    ['locale' => 'en', 'content' => 'hello'],
                    ['locale' => 'fr', 'content' => 'salut'],
                ],
            ],
        ];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de', 'en'], 2, 1, ['sidx' => 'domain', 'sord' => 'ASC']);
        $expected = [
            [
                'key' => 'key.say_goodbye',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'en', 'content' => 'goodbye'],
                    ['locale' => 'fr', 'content' => 'au revoir'],
                ],
            ],
            [
                'id' => 3,
                'key' => 'key.say_wtf',
                'domain' => 'messages',
                'translations' => [
                    ['locale' => 'en', 'content' => 'what the fuck !?!'],
                    ['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'],
                ],
            ],
        ];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de', 'en'], 2, 2, ['sidx' => 'domain', 'sord' => 'ASC']);
        $expected = [
            [
                'key' => 'key.say_hello',
                'domain' => 'superTranslations',
                'translations' => [
                    ['locale' => 'de', 'content' => 'heil'],
                    ['locale' => 'en', 'content' => 'hello'],
                    ['locale' => 'fr', 'content' => 'salut'],
                ],
            ],
        ];
        $this->assertSameTransUnit($expected, $result);
    }

    /**
     * @group odm
     */
    public function testGetTranslationsForFile(): void
    {
        $dm = $this->loadDatabase();
        $repository = $dm->getRepository(self::DOCUMENT_TRANS_UNIT_CLASS);

        $file = $dm->getRepository(self::DOCUMENT_FILE_CLASS)->findOneBy([
            'domain' => 'messages',
            'locale' => 'fr',
            'extention' => 'yml',
        ]);
        $this->assertInstanceOf(self::DOCUMENT_FILE_CLASS, $file);

        $result = $repository->getTranslationsForFile($file, false);
        $expected = [
            'key.say_goodbye' => 'au revoir',
            'key.say_wtf' => 'c\'est quoi ce bordel !?!',
        ];
        $this->assertEquals($expected, $result);

        // update a translation and then get translations with onlyUpdated = true
        $inTwoDays = new \DateTime('now');
        $inTwoDays->modify('+2 days');

        $dm->createQueryBuilder(self::DOCUMENT_TRANS_UNIT_CLASS)
           ->updateOne()
            //->field('translations.0.content')->set('changed')
           ->field('translations.0.updatedAt')->set(new Timestamp(1234, $inTwoDays->format('U')))
           ->field('translations.1.updatedAt')->set(new Timestamp(1234, $inTwoDays->format('U')))
           ->field('key')->equals('key.say_goodbye')
           ->field('domain')->equals('messages')
           ->getQuery()
           ->execute();

        $result = $repository->getTranslationsForFile($file, true);
        $expected = [
            'key.say_goodbye' => 'au revoir',
        ];
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
