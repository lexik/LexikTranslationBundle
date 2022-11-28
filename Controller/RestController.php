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

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class RestController extends AbstractController
{
    use CsrfCheckerTrait;

    private $csrfTokenManager;

    public function __construct(
        private readonly DataGridRequestHandler $dataGridRequestHandler,
        private readonly DataGridFormatter $dataGridFormatter,
        private readonly StorageInterface $translationStorage,
        private readonly TransUnitManagerInterface $transUnitManager,
    ) {
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(Request $request)
    {
        [$transUnits, $count] = $this->dataGridRequestHandler->getPage($request);

        return $this->dataGridFormatter->createListResponse($transUnits, $count);
    }

    /**
     * @param $token
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listByProfileAction(Request $request, $token)
    {
        [$transUnits, $count] = $this->dataGridRequestHandler->getPageByToken($request, $token);

        return $this->dataGridFormatter->createListResponse($transUnits, $count);
    }

    /**
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function updateAction(Request $request, $id)
    {
        $this->checkCsrf();

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
    public function deleteAction($id)
    {
        $this->checkCsrf();

        $transUnit = $this->translationStorage->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        $deleted = $this->transUnitManager->delete($transUnit);

        return new JsonResponse(['deleted' => $deleted], $deleted ? 200 : 400);
    }

    /**
     * @param integer $id
     * @param string  $locale
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteTranslationAction($id, $locale)
    {
        $this->checkCsrf();

        $transUnit = $this->translationStorage->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        $deleted = $this->transUnitManager->deleteTranslation($transUnit, $locale);

        return new JsonResponse(['deleted' => $deleted], $deleted ? 200 : 400);
    }
}
