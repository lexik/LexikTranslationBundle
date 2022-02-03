<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Util\Csrf\CsrfCheckerTrait;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridFormatter;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridRequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class RestController extends AbstractController
{
    use CsrfCheckerTrait;

    public function __construct(
        protected DataGridRequestHandler $dataGridRequestHandler,
        protected DataGridFormatter $dataGridFormatter,
        protected StorageInterface $translationStorage,
        protected TransUnitManagerInterface $transUnitManager,
        protected ?CsrfTokenManager $csrfTokenManager
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(Request $request)
    {
        [$transUnits, $count] = $this->dataGridRequestHandler->getPage($request);

        return $this->dataGridFormatter->createListResponse($transUnits, $count);
    }

    /**
     * @param Request $request
     * @param $token
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listByProfileAction(Request $request, $token)
    {
        [$transUnits, $count] = $this->dataGridRequestHandler->getPageByToken($request, $token);

        return $this->dataGridFormatter->createListResponse($transUnits, $count);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function updateAction(Request $request, $id)
    {
        $this->checkCsrf($this->csrfTokenManager, $request);

        $transUnit = $this->dataGridRequestHandler->updateFromRequest($id, $request);

        return $this->dataGridFormatter->createSingleResponse($transUnit);
    }

    /**
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(Request $request, $id)
    {
        $this->checkCsrf($this->csrfTokenManager, $request);

        $transUnit = $this->translationStorage->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        $deleted = $this->transUnitManager->delete($transUnit);

        return new JsonResponse(array('deleted' => $deleted), $deleted ? 200 : 400);
    }

    /**
     * @param integer $id
     * @param string  $locale
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteTranslationAction(Request $request, $id, $locale)
    {
        $this->checkCsrf($this->csrfTokenManager, $request);

        $transUnit = $this->translationStorage->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        $deleted = $this->transUnitManager->deleteTranslation($transUnit, $locale);

        return new JsonResponse(array('deleted' => $deleted), $deleted ? 200 : 400);
    }
}
