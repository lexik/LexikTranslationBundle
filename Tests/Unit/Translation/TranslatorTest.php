<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation;

use Lexik\Bundle\TranslationBundle\Translation\GetDatabaseResourcesListener;
use Lexik\Bundle\TranslationBundle\Translation\Translator;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\DependencyInjection\Container;

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
    public function testAddDatabaseResources()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);

        if (file_exists(sys_get_temp_dir().'/database.resources.php')) {
            unlink(sys_get_temp_dir().'/database.resources.php');
        }

        $translator = $this->createTranslator($em, sys_get_temp_dir());
        $translator->addDatabaseResources();

        $expected = array(
            'de' => array(
            	array('database', 'DB', 'messages'),
            	array('database', 'DB', 'superTranslations'),
            ),
            'en' => array(
                array('database', 'DB', 'messages'),
                array('database', 'DB', 'superTranslations'),
            ),
            'fr' => array(
                array('database', 'DB', 'messages'),
                array('database', 'DB', 'superTranslations'),
            ),
        );
        $this->assertEquals($expected, $translator->dbResources);
    }

    /**
     * @group translator
     */
    public function testRemoveCacheFile()
    {
        $cacheDir = __DIR__.'/../../../vendor/test_cache_dir';
        $this->createFakeCacheFiles($cacheDir);
        $translator = $this->createTranslator($this->getMockSqliteEntityManager(), $cacheDir);

        // remove locale 'fr'
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr.php'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr.php.meta'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr_FR.php'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr_FR.php.meta'));

        $translator->removeCacheFile('fr');

        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr.php'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr.php.meta'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr_FR.php'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr_FR.php.meta'));

        // remove locale 'en'
        $this->assertTrue(file_exists($cacheDir.'/catalogue.en.php'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.en.php.meta'));
        $translator->removeCacheFile('en');
        $this->assertFalse(file_exists($cacheDir.'/catalogue.en.php'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.en.php.meta'));
    }

    /**
     * @group translator
     */
    public function testRemoveLocalesCacheFiles()
    {
        $cacheDir = __DIR__.'/../../../vendor/test_cache_dir';
        $this->createLocalesCacheFiles($cacheDir);
        $translator = $this->createTranslator($this->getMockSqliteEntityManager(), $cacheDir);
        $this->removeLocalesCacheFiles('CanalTP', $cacheDir, $translator);

        $customCacheDir = __DIR__.'/../../../vendor/custom/';
        $kernel = $this->getMock('AppKernel', array('getCustomCacheDir'));
        $kernel->expects($this->once())
            ->method('getCustomCacheDir')
            ->will($this->returnValue($customCacheDir));
        $customCacheDir .= 'test_cache_dir';
        $this->createLocalesCacheFiles($customCacheDir);
        $translator = $this->createTranslator($this->getMockSqliteEntityManager(), $cacheDir, $kernel);
        $this->removeLocalesCacheFiles('custom', $customCacheDir, $translator);
    }

    protected function createLocalesCacheFiles($cacheDir)
    {
        $this->createFakeCacheFiles($cacheDir);

        $this->assertTrue(file_exists($cacheDir.'/database.resources.php'));
        $this->assertTrue(file_exists($cacheDir.'/database.resources.php.meta'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr.php'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr.php.meta'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr_FR.php'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.fr_FR.php.meta'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.en.php'));
        $this->assertTrue(file_exists($cacheDir.'/catalogue.en.php.meta'));
    }

    protected function removeLocalesCacheFiles($client, $cacheDir, $translator)
    {
        $translator->removeLocalesCacheFiles($client, array('fr', 'en'));

        $this->assertFalse(file_exists($cacheDir.'/database.resources.php'));
        $this->assertFalse(file_exists($cacheDir.'/database.resources.php.meta'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr.php'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr.php.meta'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr_FR.php'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.fr_FR.php.meta'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.en.php'));
        $this->assertFalse(file_exists($cacheDir.'/catalogue.en.php.meta'));
    }

    protected function createTranslator($em, $cacheDir, $kernel=null)
    {
        $listener = new GetDatabaseResourcesListener($this->getORMStorage($em), 'xxxxx');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            'lexik_translation.event.get_database_resources',
            array($listener, 'onGetDatabaseResources')
        );

        $container = new Container();
        $container->set('event_dispatcher', $dispatcher);
        if ($kernel) {
            $container->set('kernel', $kernel);
        }
        $container->compile();

        $loaderIds = array();
        $options = array(
            'cache_dir' => $cacheDir,
        );

        return new TranslatorMock($container, new MessageSelector(), $loaderIds, $options);
    }

    protected function createFakeCacheFiles($cacheDir)
    {
        if(!is_dir($cacheDir)) {
            mkdir($cacheDir, '0777', true);
        }

        touch($cacheDir.'/catalogue.fr.php');
        touch($cacheDir.'/catalogue.fr.php.meta');

        touch($cacheDir.'/catalogue.fr_FR.php');
        touch($cacheDir.'/catalogue.fr_FR.php.meta');

        touch($cacheDir.'/catalogue.en.php');
        touch($cacheDir.'/catalogue.en.php.meta');

        touch($cacheDir.'/database.resources.php');
        touch($cacheDir.'/database.resources.php.meta');
    }
}

class TranslatorMock extends Translator
{
    public $dbResources = array();

    public function addResource($format, $resource, $locale, $domain = 'messages')
    {
        if ('database' == $format) {
            $this->dbResources[$locale][] = array($format, $resource, $domain);
        }

        parent::addResource($format, $resource, $locale, $domain);
    }
}
