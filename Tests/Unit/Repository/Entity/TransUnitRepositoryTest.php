<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Repository\Entity;

use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

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
        $em = $this->loadDatabase();
        $repository = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS);

        $results = $repository->getAllDomainsByLocale();
        $expected = [['locale' => 'de', 'domain' => 'superTranslations'], ['locale' => 'en', 'domain' => 'messages'], ['locale' => 'en', 'domain' => 'superTranslations'], ['locale' => 'fr', 'domain' => 'messages'], ['locale' => 'fr', 'domain' => 'superTranslations']];

        $this->assertSame($expected, $results);
    }

    /**
     * @group orm
     */
    public function testGetAllDomains()
    {
        $em = $this->loadDatabase(true);
        $repository = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS);

        $results = $repository->getAllDomains();
        $expected = ['messages', 'superTranslations'];

        $this->assertSame($expected, $results);
    }

    /**
     * @group orm
     */
    public function testGetAllByLocaleAndDomain()
    {
        $em = $this->loadDatabase();
        $repository = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS);

        $results = $repository->getAllByLocaleAndDomain('de', 'messages');
        $expected = [];
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('de', 'superTranslations');
        $expected = [['id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => [['locale' => 'de', 'content' => 'heil']]]];
        $this->assertSameTransUnit($expected, $results);

        $results = $repository->getAllByLocaleAndDomain('en', 'messages');
        $expected = [['id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => [['locale' => 'en', 'content' => 'goodbye']]], ['id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => [['locale' => 'en', 'content' => 'what the fuck !?!']]]];
        $this->assertSameTransUnit($expected, $results);
    }

    /**
     * @group orm
     */
    public function testCount()
    {
        $em = $this->loadDatabase(true);
        $repository = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS);

        $this->assertEquals(3, $repository->count(['locales' => ['fr', 'de', 'en'], 'filters' => []]));
        $this->assertEquals(3, $repository->count(['locales' => ['fr', 'it'], 'filters' => []]));
        $this->assertEquals(3, $repository->count(['locales' => ['fr', 'de'], 'filters' => ['_search' => false, 'key' => 'good']]));
        $this->assertEquals(1, $repository->count(['locales' => ['fr', 'de'], 'filters' => ['_search' => true, 'key' => 'good']]));
        $this->assertEquals(1, $repository->count(['locales' => ['en', 'de'], 'filters' => ['_search' => true, 'domain' => 'super']]));
        $this->assertEquals(1, $repository->count(['locales' => ['en', 'fr', 'de'], 'filters' => ['_search' => true, 'key' => 'hel', 'domain' => 'uper']]));
        $this->assertEquals(2, $repository->count(['locales' => ['en', 'de'], 'filters' => ['_search' => true, 'key' => 'say', 'domain' => 'ssa']]));
    }

    /**
     * @group orm
     */
    public function testGetTransUnitList()
    {
        $em = $this->loadDatabase(true);
        $repository = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS);

        $result = $repository->getTransUnitList(['fr', 'de'], 10, 1, ['sidx' => 'key', 'sord' => 'ASC']);
        $expected = [['id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'au revoir']]], ['id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => [['locale' => 'de', 'content' => 'heil'], ['locale' => 'fr', 'content' => 'salut']]], ['id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!']]]];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de'], 10, 1, ['sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess']);
        $expected = [['id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!']]], ['id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'au revoir']]]];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de'], 10, 1, ['sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'domain' => 'mess', 'key' => 'oo']);
        $expected = [['id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'au revoir']]]];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'en'], 10, 1, ['sidx' => 'key', 'sord' => 'DESC', '_search' => true, 'fr' => 'alu']);
        $expected = [['id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => [['locale' => 'en', 'content' => 'hello'], ['locale' => 'fr', 'content' => 'salut']]]];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de', 'en'], 2, 1, ['sidx' => 'domain', 'sord' => 'ASC']);
        $expected = [['id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => [['locale' => 'en', 'content' => 'goodbye'], ['locale' => 'fr', 'content' => 'au revoir']]], ['id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => [['locale' => 'en', 'content' => 'what the fuck !?!'], ['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!']]]];
        $this->assertSameTransUnit($expected, $result);

        $result = $repository->getTransUnitList(['fr', 'de', 'en'], 2, 2, ['sidx' => 'domain', 'sord' => 'ASC']);
        $expected = [['id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => [['locale' => 'de', 'content' => 'heil'], ['locale' => 'en', 'content' => 'hello'], ['locale' => 'fr', 'content' => 'salut']]]];
        $this->assertSameTransUnit($expected, $result);
    }

    /**
     * @group orm
     */
    public function testGetTranslationsForFile()
    {
        $em = $this->loadDatabase();
        $repository = $em->getRepository(self::ENTITY_TRANS_UNIT_CLASS);

        $file = $em->getRepository(self::ENTITY_FILE_CLASS)->findOneBy(['domain'    => 'messages', 'locale'    => 'fr', 'extention' => 'yml']);
        $this->assertInstanceOf(self::ENTITY_FILE_CLASS, $file);

        $result = $repository->getTranslationsForFile($file, false);
        $expected = ['key.say_goodbye' => 'au revoir', 'key.say_wtf'     => 'c\'est quoi ce bordel !?!'];
        $this->assertEquals($expected, $result);

        // update a translation and then get translations with onlyUpdated = true
        $now = new \DateTime('now');
        $now->modify('+2 days');

        $em->createQueryBuilder()
            ->update(self::ENTITY_TRANSLATION_CLASS, 't')
            ->set('t.updatedAt', ':date')
            ->where('t.locale = :locale AND t.content = :content')
            ->setParameter('locale', 'fr')
            ->setParameter('content', 'au revoir')
            ->setParameter('date', $now->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute();

        $result = $repository->getTranslationsForFile($file, true);
        $expected = ['key.say_goodbye' => 'au revoir'];
        $this->assertEquals($expected, $result);
    }

    protected function assertSameTransUnit($expected, $result)
    {
        $this->assertEquals(is_countable($expected) ? count($expected) : 0, is_countable($result) ? count($result) : 0);

        foreach ($expected as $i => $transUnit) {
            $this->assertEquals($transUnit['id'], $result[$i]['id']);
            $this->assertEquals($transUnit['key'], $result[$i]['key']);
            $this->assertEquals($transUnit['domain'], $result[$i]['domain']);

            $this->assertEquals(is_countable($transUnit['translations']) ? count($transUnit['translations']) : 0, is_countable($result[$i]['translations']) ? count($result[$i]['translations']) : 0);

            /*
             * $expected has a fixed order. It is unsafe to rely on the order in which
             * items are returned from the database. Therefore, the results from the database
             * must be indexed by locale, before making any assertions, otherwise, random false negatives
             * will occur.
             */
            $translationsByLocale = [];

            foreach ($result[$i]['translations'] as $row) {
                $translationsByLocale[$row['locale']] = $row;
            }

            foreach ($transUnit['translations'] as $j => $translation) {
                $locale = $translation['locale'];
                $this->assertEquals($translation['content'], $translationsByLocale[$locale]['content']);
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
