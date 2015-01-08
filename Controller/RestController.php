<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class RestController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction()
    {
        list($transUnits, $count) = $this->get('lexik_translation.data_grid.request_handler')->getPage($this->getRequest());

        return $this->get('lexik_translation.data_grid.formatter')->createListResponse($transUnits, $count);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @throws NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateAction(Request $request, $id)
    {
        if (!$request->isMethod('PUT')) {
            throw $this->createNotFoundException('Invalid request method.');
        }

        $transUnit = $this->get('lexik_translation.data_grid.request_handler')->updateFromRequest($id, $request);

        return $this->get('lexik_translation.data_grid.formatter')->createSingleResponse($transUnit);
    }
}
