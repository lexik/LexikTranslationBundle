<?php

namespace Lexik\Bundle\TranslationBundle\EventDispatcher;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManager;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Finder\Finder;

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
     * @var array
     */
    private $localeManager;

    /**
     * @var int
     */
    private $cacheInterval;

    /**
     * Constructor
     *
     * @param StorageInterface    $storage
     * @param TranslatorInterface $translator
     * @param string              $cacheDirectory
     * @param LocaleManager       $localeManager
     * @param int                 $cacheInterval
     */
    public function __construct(StorageInterface $storage, TranslatorInterface $translator, $cacheDirectory, LocaleManager $localeManager, $cacheInterval)
    {
        $this->storage = $storage;
        $this->cacheDirectory = $cacheDirectory;
        $this->translator = $translator;
        $this->localeManager = $localeManager;
        $this->cacheInterval = $cacheInterval;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest() && $this->isCacheExpired()) {
            $lastUpdateTime = $this->storage->getLatestUpdatedAt();

            if ($lastUpdateTime instanceof \DateTime) {
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
}
