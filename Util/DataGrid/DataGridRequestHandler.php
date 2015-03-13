<?php

namespace Lexik\Bundle\TranslationBundle\Util\DataGrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

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
     * @param TransUnitManagerInterface $transUnitManager
     * @param StorageInterface          $storage
     * @param array                     $managedLocales
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, StorageInterface $storage, array $managedLocales)
    {
        $this->transUnitManager = $transUnitManager;
        $this->storage = $storage;
        $this->managedLocales = $managedLocales;
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
