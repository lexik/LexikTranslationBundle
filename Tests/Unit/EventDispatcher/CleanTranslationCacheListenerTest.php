<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\EventDispatcher;

use Lexik\Bundle\TranslationBundle\EventDispatcher\CleanTranslationCacheListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Finder\Finder;

/**
 * Unit test for CleanTranslationCacheListener class
 *
 * @author Max Milazzo maxmilazzo@timeout.com
 */
class CleanTranslationCacheListenerTest extends \PHPUnit_Framework_TestCase
{

    private $tempDir;

    public function setUp()
    {
        $this->tempDir = \sys_get_temp_dir() . '/translations';
    }

    public function testDefaultLocale()
    {
        $request = Request::create('/');

        $date = new \DateTime;

        if (!\file_exists($this->tempDir)) {
            \mkdir($this->tempDir);
        }

        \touch($this->tempDir . '/messages.en.yml', time() - 3600);

        $storage = $this->getMockBuilder('Lexik\Bundle\TranslationBundle\Storage\StorageInterface')
                ->disableOriginalConstructor()
                ->setMethods(array())
                ->getMock();

        $storage->expects($this->any())->method('getLatestUpdatedAt')->will($this->returnValue($date));

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $translator = $this->getMock('Lexik\Bundle\TranslationBundle\Translation\Translator', array(), array($container, new MessageSelector));

        $translator->expects($this->any())->method('removeLocalesCacheFiles')->will($this->returnValue(true));

        $listener = new CleanTranslationCacheListener($storage, $translator, \sys_get_temp_dir(), array('en'), 600);

        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);

        $this->assertTrue(file_exists($this->tempDir . '/cache_timestamp'));
        $this->assertEquals(1, $this->countFiles($date));

        \touch($this->tempDir . '/messages.en.yml');

        $listener->onKernelRequest($event);

        $this->assertEquals(0, $this->countFiles($date));
    }

    private function getEvent(Request $request)
    {
        return new GetResponseEvent($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $request, HttpKernelInterface::MASTER_REQUEST);
    }

    private function countFiles($lastUpdateTime)
    {
        $finder = new Finder();
        $finder->files()
                ->in($this->tempDir)
                ->date('< ' . $lastUpdateTime->format('Y-m-d H:i:s'));

        return $finder->count();
    }

    public function tearDown()
    {
        \array_map('unlink', \glob($this->tempDir . "/*"));
    }

}
