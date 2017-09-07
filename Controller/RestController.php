<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class RestController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction(Request $request)
    {
        list($transUnits, $count) = $this->get('lexik_translation.data_grid.request_handler')->getPage($request);

        return $this->get('lexik_translation.data_grid.formatter')->createListResponse($transUnits, $count);
    }

    /**
     * @param Request $request
     * @param $token
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listByProfileAction(Request $request, $token)
    {
        list($transUnits, $count) = $this->get('lexik_translation.data_grid.request_handler')->getPageByToken($request, $token);

        return $this->get('lexik_translation.data_grid.formatter')->createListResponse($transUnits, $count);
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
        if (!$request->isMethod('PUT')) {
            throw $this->createNotFoundException(sprintf('Invalid request method %s, PUT only.', $request->getMethod()));
        }

        $this->checkCsrf('lexik-translation');

        $transUnit = $this->get('lexik_translation.data_grid.request_handler')->updateFromRequest($id, $request);

        return $this->get('lexik_translation.data_grid.formatter')->createSingleResponse($transUnit);
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
        $this->checkCsrf('lexik-translation');

        $transUnit = $this->get('lexik_translation.translation_storage')->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        $deleted = $this->get('lexik_translation.trans_unit.manager')->delete($transUnit);

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
    public function deleteTranslationAction($id, $locale)
    {
        $this->checkCsrf('lexik-translation');

        $transUnit = $this->get('lexik_translation.translation_storage')->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        $deleted = $this->get('lexik_translation.trans_unit.manager')->deleteTranslation($transUnit, $locale);

        return new JsonResponse(array('deleted' => $deleted), $deleted ? 200 : 400);
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * @param string $id    The id used when generating the token
     * @param string $query
     */
    protected function checkCsrf($id, $query = '_token')
    {
        if (!$this->container->has('security.csrf.token_manager')) {
            return;
        }

        $request = $this->get('request_stack')->getMasterRequest();

        if (!$isCrsfTokenValid = $this->isCsrfTokenValid($id, $request->get($query))) {
            throw new InvalidCsrfTokenException();
        }
    }
}
