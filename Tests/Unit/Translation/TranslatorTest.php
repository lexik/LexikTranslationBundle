<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Lexik\Bundle\TranslationBundle\EventDispatcher\GetDatabaseResourcesListener;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;
use Lexik\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Formatter\MessageFormatter;

/**
 * Translator tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslatorTest extends BaseUnitTestCase
{
    /**
     * @group translator
     */
    public function testAddDatabaseResources(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);

        if (file_exists(sys_get_temp_dir() . '/database.resources.php')) {
            unlink(sys_get_temp_dir() . '/database.resources.php');
        }

        $translator = $this->createTranslator($em, sys_get_temp_dir());
        $translator->addDatabaseResources();

        $expected = [
            'de' => [
                ['database', 'DB', 'superTranslations']
            ],
            'en' => [
                ['database', 'DB', 'messages'],
                ['database', 'DB', 'superTranslations']
            ],
            'fr' => [
                ['database', 'DB', 'messages'],
                ['database', 'DB', 'superTranslations'],
            ]
        ];

        $this->assertEqualsCanonicalizing($expected['en'], $translator->dbResources['en']);
        $this->assertEqualsCanonicalizing($expected['fr'], $translator->dbResources['fr']);
        $this->assertEqualsCanonicalizing($expected['de'], $translator->dbResources['de']);
        $this->assertEqualsCanonicalizing($expected, $translator->dbResources);
    }

    /**
     * @group translator
     */
    public function testRemoveCacheFile(): void
    {
        $cacheDir = __DIR__ . '/../../../vendor/test_cache_dir';
        $this->createFakeCacheFiles($cacheDir);
        $translator = $this->createTranslator($this->getMockSqliteEntityManager(), $cacheDir);

        // remove locale 'fr'
        $this->assertFileExists($cacheDir . '/catalogue.fr.php');
        $this->assertFileExists($cacheDir . '/catalogue.fr.php.meta');
        $this->assertFileExists($cacheDir . '/catalogue.fr_FR.php');
        $this->assertFileExists($cacheDir . '/catalogue.fr_FR.php.meta');

        $translator->removeCacheFile('fr');

        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr.php');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr.php.meta');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr_FR.php');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr_FR.php.meta');

        // remove locale 'en'
        $this->assertFileExists($cacheDir . '/catalogue.en.php');
        $this->assertFileExists($cacheDir . '/catalogue.en.php.meta');
        $translator->removeCacheFile('en');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.en.php');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.en.php.meta');
    }

    /**
     * @group translator
     */
    public function testRemoveLocalesCacheFiles(): void
    {
        $cacheDir = __DIR__ . '/../../../vendor/test_cache_dir';
        $this->createFakeCacheFiles($cacheDir);
        $translator = $this->createTranslator($this->getMockSqliteEntityManager(), $cacheDir);

        $this->assertFileExists($cacheDir . '/database.resources.php');
        $this->assertFileExists($cacheDir . '/database.resources.php.meta');
        $this->assertFileExists($cacheDir . '/catalogue.fr.php');
        $this->assertFileExists($cacheDir . '/catalogue.fr.php.meta');
        $this->assertFileExists($cacheDir . '/catalogue.fr_FR.php');
        $this->assertFileExists($cacheDir . '/catalogue.fr_FR.php.meta');
        $this->assertFileExists($cacheDir . '/catalogue.en.php');
        $this->assertFileExists($cacheDir . '/catalogue.en.php.meta');

        $translator->removeLocalesCacheFiles(['fr', 'en']);

        $this->assertFileDoesNotExist($cacheDir . '/database.resources.php');
        $this->assertFileDoesNotExist($cacheDir . '/database.resources.php.meta');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr.php');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr.php.meta');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr_FR.php');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.fr_FR.php.meta');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.en.php');
        $this->assertFileDoesNotExist($cacheDir . '/catalogue.en.php.meta');
    }

    protected function createTranslator($em, $cacheDir): TranslatorMock
    {
        $listener = new GetDatabaseResourcesListener($this->getORMStorage($em), 'xxxxx');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            GetDatabaseResourcesEvent::class,
            $listener->onGetDatabaseResources(...)
        );

        $container = new Container();
        $container->setParameter('kernel.default_locale', 'en');
        $container->set('event_dispatcher', $dispatcher);
        $container->compile();

        $innerTranslator = new \Symfony\Component\Translation\Translator('en', new MessageFormatter());

        $options = [
            'cache_dir' => $cacheDir,
            'debug' => true,
            'resources_type' => 'all',
        ];

        return new TranslatorMock(
            translator: $innerTranslator,
            container: $container,
            loaderIds: [],
            options: $options
        );
    }

    protected function createFakeCacheFiles($cacheDir): void
    {
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }

        touch($cacheDir . '/catalogue.fr.php');
        touch($cacheDir . '/catalogue.fr.php.meta');

        touch($cacheDir . '/catalogue.fr_FR.php');
        touch($cacheDir . '/catalogue.fr_FR.php.meta');

        touch($cacheDir . '/catalogue.en.php');
        touch($cacheDir . '/catalogue.en.php.meta');

        touch($cacheDir . '/database.resources.php');
        touch($cacheDir . '/database.resources.php.meta');
    }
}

class TranslatorMock extends Translator
{
    public array $dbResources = [];

    public function addResource(string $format, mixed $resource, string $locale, ?string $domain = null): void
    {
        if ('database' === $format) {
            $this->dbResources[$locale][] = [$format, $resource, $domain];
        }

        parent::addResource($format, $resource, $locale, $domain);
    }
}
