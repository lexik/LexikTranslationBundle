<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class RestController extends Controller
{
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

        return new JsonResponse(array(
            'translations' => $transUnits,
            'count'        => $count,
        ));
    }

    public function updateAction()
    {
//         $request = $this->get('request');

//         if ( ! $request->isXmlHttpRequest() ) {
//             throw new NotFoundHttpException();
//         }

//         $response = new Response('', 200, array('Content-type' => 'application/json'));

//         if ('edit' == $request->request->get('oper')) {
//             $storage = $this->get('lexik_translation.translation_storage');
//             $transUnit = $storage->getTransUnitById($request->request->get('id'));

//             if (!($transUnit instanceof TransUnit)) {
//                 throw new NotFoundHttpException();
//             }

//             $translationsContent = array();
//             foreach ($this->getManagedLocales() as $locale) {
//                 $translationsContent[$locale] = $request->request->get($locale);
//             }

//             $this->get('lexik_translation.trans_unit.manager')->updateTranslationsContent($transUnit, $translationsContent);

//             if ($transUnit instanceof TransUnitDocument) {
//                 $transUnit->convertMongoTimestamp();
//             }

//             $storage->flush();

//             $response->setContent(json_encode(array('message' => sprintf('TransUnit #%d updated.', $transUnit->getId()))));
//         }

//         return $response;
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
