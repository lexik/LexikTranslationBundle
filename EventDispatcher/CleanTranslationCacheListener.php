<?php

namespace Lexik\Bundle\TranslationBundle\EventDispatcher;

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
    private $managedLocales;

    /**
     * Constructor
     *
     * @param StorageInterface    $storage
     * @param TranslatorInterface $translator
     * @param string              $cacheDirectory
     * @param array               $managedLocales
     */
    public function __construct(StorageInterface $storage, TranslatorInterface $translator, $cacheDirectory, $managedLocales)
    {
        $this->storage = $storage;
        $this->cacheDirectory = $cacheDirectory;
        $this->translator = $translator;
        $this->managedLocales = $managedLocales;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            throw new \RuntimeException('TODO Bwaaahhhaha');

//            $qb = $this->em->createQueryBuilder();
//            $qb->select('max(t.updatedAt)')
//                ->from('LexikTranslationBundle:Translation', 't');
//
//            $lastUpdateTime = $qb->getQuery()->getSingleScalarResult();
//
//            $finder = new Finder();
//            $finder->files()->in($this->cacheDirectory.'/translations')->date('< '.$lastUpdateTime);
//
//            if ($finder->count() > 0) {
//                $this->translator->removeLocalesCacheFiles($this->managedLocales);
//            }
        }
    }
}
