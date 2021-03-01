<?php

namespace Lexik\Bundle\TranslationBundle\EventDispatcher;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class CleanTranslationCacheListener
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var LocaleManagerInterface
     */
    private $localeManager;

    /**
     * @var int
     */
    private $cacheInterval;

    /**
     * Constructor
     *
     * @param StorageInterface       $storage
     * @param TranslatorInterface    $translator
     * @param string                 $cacheDirectory
     * @param LocaleManagerInterface $localeManager
     * @param int                    $cacheInterval
     */
    public function __construct(StorageInterface $storage, TranslatorInterface $translator, $cacheDirectory, LocaleManagerInterface $localeManager, $cacheInterval)
    {
        $this->storage = $storage;
        $this->cacheDirectory = $cacheDirectory;
        $this->translator = $translator;
        $this->localeManager = $localeManager;
        $this->cacheInterval = $cacheInterval;
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest() && $this->isCacheExpired()) {
            $lastUpdateTime = $this->storage->getLatestUpdatedAt();

            if ($lastUpdateTime instanceof \DateTime) {
                $this->checkCacheFolder();

                $finder = new Finder();
                $finder->files()
                    ->in($this->cacheDirectory.'/translations')
                    ->date('< '.$lastUpdateTime->format('Y-m-d H:i:s'));

                if ($finder->count() > 0) {
                    $this->translator->removeLocalesCacheFiles($this->localeManager->getLocales());
                }
            }
        }
    }

    /**
    * Checks if cache has expired
    *
    * @return boolean
    */
    private function isCacheExpired()
    {
        if (empty($this->cacheInterval)) {
            return true;
        }

        $cache_file = $this->cacheDirectory.'/translations/cache_timestamp';
        $cache_dir  =$this->cacheDirectory.'/translations';
        if ('\\' === DIRECTORY_SEPARATOR) {
            $cache_file = strtr($cache_file, '/', '\\');
            $cache_dir = strtr($cache_dir, '/', '\\');
        }
        if (!\is_dir($cache_dir)) {
            \mkdir($cache_dir);
        }        
        if (!\file_exists($cache_file)) {
            \touch($cache_file);
            return true;
        }
        $expired = false;
        if ((\time() - \filemtime($cache_file)) > $this->cacheInterval) {
            \file_put_contents($cache_file, \time());
            $expired = true;
        }

        return $expired;
    }

    private function checkCacheFolder()
    {
        if (!is_dir($dirName = $this->cacheDirectory.'/translations') && !mkdir($dirName) && !is_dir($dirName)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirName));
        }
    }
}
