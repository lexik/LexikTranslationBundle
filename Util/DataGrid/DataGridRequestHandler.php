<?php

namespace Lexik\Bundle\TranslationBundle\Util\DataGrid;

use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridRequestHandler
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var array
     */
    protected $managedLocales;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var bool
     */
    protected $createMissing;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param StorageInterface          $storage
     * @param array                     $managedLocales
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, StorageInterface $storage, array $managedLocales)
    {
        $this->transUnitManager = $transUnitManager;
        $this->storage = $storage;
        $this->managedLocales = $managedLocales;
        $this->createMissing = false;
    }

    /**
     * @param Profiler $profiler
     */
    public function setProfiler(Profiler $profiler = null)
    {
        $this->profiler = $profiler;
    }

    /**
     * @param bool $createMissing
     */
    public function setCreateMissing($createMissing)
    {
        $this->createMissing = (bool) $createMissing;
    }

    /**
     * Returns an array with the trans unit for the current page and the total of trans units
     *
     * @param Request $request
     * @return array
     */
    public function getPage(Request $request)
    {
        $all = $request->query->all();
        $parameters = array();

        array_walk($all, function ($value, $key) use (&$parameters) {
            if ($key != '_search') {
                $key = trim($key, '_');
                $value = trim($value, '_');
            }
            $parameters[$key] = $value;
        });

        $transUnits = $this->storage->getTransUnitList(
            $this->managedLocales,
            $request->query->get('rows', 20),
            $request->query->get('page', 1),
            $parameters
        );

        $count = $this->storage->countTransUnits($this->managedLocales, $parameters);

        return array($transUnits, $count);
    }

    /**
     * Get a profile's translation messages based on a previous Profiler token.
     *
     * @param $token by which a Profile can be found in the Profiler
     *
     * @return array with collection of TransUnits and it's count
     */
    public function getByToken($token)
    {
        if (null === $this->profiler) {
            throw new \RuntimeException('Invalid profiler instance.');
        }

        $profile = $this->profiler->loadProfile($token);

        // In case no results were found
        if (!$profile instanceof Profile) {
            return array(array(), 0);
        }

        try {
            /** @var TranslationDataCollector $collector */
            $collector = $profile->getCollector('translation');
            $messages = $collector->getMessages();

            $transUnits = array();
            foreach ($messages as $message) {
                $transUnit = $this->storage->getTransUnitByKeyAndDomain($message['id'], $message['domain']);

                if ($transUnit instanceof TransUnit) {
                    $transUnits[] = $transUnit;
                } elseif (true === $this->createMissing) {
                    $transUnits[] = $this->transUnitManager->create($message['id'], $message['domain'], true);
                }
            }

            return array($transUnits, count($transUnits));

        } catch (\InvalidArgumentException $e) {

            // Translation collector is a 2.7 feature
            return array(array(), 0);
        }
    }

    /**
     * Updates a trans unit from the request.
     *
     * @param integer $id
     * @param Request $request
     * @throws NotFoundHttpException
     * @return \Lexik\Bundle\TranslationBundle\Model\TransUnit
     */
    public function updateFromRequest($id, Request $request)
    {
        $transUnit = $this->storage->getTransUnitById($id);

        if (!$transUnit) {
            throw new NotFoundHttpException(sprintf('No TransUnit found for "%s"', $id));
        }

        $translationsContent = array();
        foreach ($this->managedLocales as $locale) {
            $translationsContent[$locale] = $request->request->get($locale);
        }

        $this->transUnitManager->updateTranslationsContent($transUnit, $translationsContent);

        if ($transUnit instanceof TransUnitDocument) {
            $transUnit->convertMongoTimestamp();
        }

        $this->storage->flush();

        return $transUnit;
    }
}
