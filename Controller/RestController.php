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

    public function listAction(Request $request): JsonResponse
    {
        [$transUnits, $count] = $this->dataGridRequestHandler->getPage($request);

        return $this->dataGridFormatter->createListResponse($transUnits, $count);
    }

    public function listByProfileAction(Request $request, string $token): JsonResponse
    {
        [$transUnits, $count] = $this->dataGridRequestHandler->getPageByToken($request, $token);

        return $this->dataGridFormatter->createListResponse($transUnits, $count);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function updateAction(Request $request, int $id): JsonResponse
    {
        $this->checkCsrf();

        $transUnit = $this->dataGridRequestHandler->updateFromRequest($id, $request);

        return $this->dataGridFormatter->createSingleResponse($transUnit);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(int $id): JsonResponse
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteTranslationAction(int $id, string $locale): JsonResponse
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
