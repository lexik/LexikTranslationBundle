<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        $locales = $this->getManagedLocales();
        $storage = $this->get('lexik_translation.translation_storage');

        $transUnits = $storage->getTransUnitList(
            $locales,
            $this->get('request')->query->get('rows', 20),
            $this->get('request')->query->get('page', 1),
            $this->get('request')->query->all()
        );

        $count = $storage->countTransUnits($locales, $this->get('request')->query->all());

        return $this->get('lexik_translation.data_grid_formater')->createResponse($transUnits, $count);
    }

    /**
     * @throws NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateAction($id)
    {
        $request = $this->get('request');

        if (!$request->isMethod('PUT')) {
            throw $this->createNotFoundException('Invalid request method.');
        }

        $storage = $this->get('lexik_translation.translation_storage');
        $transUnit = $storage->getTransUnitById($id);

        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for "%s"', $id));
        }

        $translationsContent = array();
        foreach ($this->getManagedLocales() as $locale) {
            $translationsContent[$locale] = $request->request->get($locale);
        }

        $this->get('lexik_translation.trans_unit.manager')->updateTranslationsContent($transUnit, $translationsContent);

        if ($transUnit instanceof TransUnitDocument) {
            $transUnit->convertMongoTimestamp();
        }

        $storage->flush();

        return new JsonResponse( array('message' => sprintf('TransUnit #%d updated.', $transUnit->getId())) );
    }

    /**
     * Returns managed locales.
     *
     * @return array
     */
    protected function getManagedLocales()
    {
        return $this->container->getParameter('lexik_translation.managed_locales');
    }
}
