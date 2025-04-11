<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Loader;

use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Translation\Loader\DatabaseLoader;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * DatabaseLoader tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseLoaderTest extends BaseUnitTestCase
{
    /**
     * @group loader
     */
    public function testLoad()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);

        $loader = new DatabaseLoader($this->getORMStorage($em), TransUnit::class);

        $catalogue = $loader->load(null, 'it');
        $this->assertInstanceOf(MessageCatalogue::class, $catalogue);
        $this->assertEquals([], $catalogue->all());
        $this->assertEquals('it', $catalogue->getLocale());

        $catalogue = $loader->load(null, 'fr');
        $expectedTranslations = ['messages' => ['key.say_goodbye' => 'au revoir', 'key.say_wtf'     => 'c\'est quoi ce bordel !?!']];
        $this->assertInstanceOf(MessageCatalogue::class, $catalogue);
        $this->assertEquals($expectedTranslations, $catalogue->all());
        $this->assertEquals('fr', $catalogue->getLocale());

        $catalogue = $loader->load(null, 'en', 'superTranslations');
        $expectedTranslations = ['superTranslations' => ['key.say_hello' => 'hello']];
        $this->assertInstanceOf(MessageCatalogue::class, $catalogue);
        $this->assertEquals($expectedTranslations, $catalogue->all());
        $this->assertEquals('en', $catalogue->getLocale());
    }
}
