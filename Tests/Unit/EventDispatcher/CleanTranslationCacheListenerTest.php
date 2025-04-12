<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\EventDispatcher;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Lexik\Bundle\TranslationBundle\Translation\Translator;
use Lexik\Bundle\TranslationBundle\EventDispatcher\CleanTranslationCacheListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Finder\Finder;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CleanTranslationCacheListener class
 *
 * @author Max Milazzo maxmilazzo@timeout.com
 */
class CleanTranslationCacheListenerTest extends TestCase
{

    private $tempDir;

    public function setUp(): void
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

        $storage = $this->getMockBuilder(StorageInterface::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();

        $storage->expects($this->any())->method('getLatestUpdatedAt')->will($this->returnValue($date));

        $container = $this->getMock(ContainerInterface::class);

        $translator = $this->getMock(Translator::class, [], [$container, new MessageSelector]);

        $translator->expects($this->any())->method('removeLocalesCacheFiles')->will($this->returnValue(true));

        $listener = new CleanTranslationCacheListener($storage, $translator, \sys_get_temp_dir(), ['en'], 600);

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
        return new GetResponseEvent($this->getMock(HttpKernelInterface::class), $request, HttpKernelInterface::MASTER_REQUEST);
    }

    private function countFiles($lastUpdateTime)
    {
        $finder = new Finder();
        $finder->files()
                ->in($this->tempDir)
                ->date('< ' . $lastUpdateTime->format('Y-m-d H:i:s'));

        return $finder->count();
    }

    public function tearDown(): void
    {
        \array_map('unlink', \glob($this->tempDir . "/*"));
    }

}
