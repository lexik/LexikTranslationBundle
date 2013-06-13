<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Model\File;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Form\TransUnitType;
use Lexik\Bundle\TranslationBundle\Util\JQGrid\Mapper;

/**
 * Translations edition controlller.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class EditionController extends Controller
{
    /**
     * List trans unit element in json format.
     *
     * @return \Symfony\Component\HttpFoundation\Response
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

        $jqGridMapper = new Mapper($this->get('request'), $transUnits, $count);

        $response = new Response($jqGridMapper->generate($locales));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Display a javascript grid to edit trans unit elements.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gridAction()
    {
        return $this->render('LexikTranslationBundle:Edition:grid.html.twig', array(
            'layout'    => $this->container->getParameter('lexik_translation.base_layout'),
            'inputType' => $this->container->getParameter('lexik_translation.grid_input_type'),
            'locales'   => $this->getManagedLocales(),
        ));
    }

    /**
     * Update a trans unit element from the javascript grid.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction()
    {
        $request = $this->get('request');

        if ( ! $request->isXmlHttpRequest() ) {
            throw new NotFoundHttpException();
        }

        $response = new Response('', 200, array('Content-type' => 'application/json'));

        if ('edit' == $request->request->get('oper')) {
            $storage = $this->get('lexik_translation.translation_storage');
            $transUnit = $storage->getTransUnitById($request->request->get('id'));

            if (!($transUnit instanceof TransUnit)) {
                throw new NotFoundHttpException();
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

            $response->setContent(json_encode(array('message' => sprintf('TransUnit #%d updated.', $transUnit->getId()))));
        }

        return $response;
    }

    /**
     * Remove cache files for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invalidateCacheAction()
    {
        $this->get('translator')->removeLocalesCacheFiles($this->getManagedLocales());

        /** @var $session Session */
        $session = $this->get('session');

        $session->getFlashBag()->set('success', $this->get('translator')->trans('translations.cache_removed', array(), 'LexikTranslationBundle'));

        return $this->redirect($this->generateUrl('lexik_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        $storage = $this->get('lexik_translation.translation_storage');
        $transUnit = $this->get('lexik_translation.trans_unit.manager')->newInstance($this->getManagedLocales());

        $options = array(
            'domains'           => $storage->getTransUnitDomains(),
            'data_class'        => $storage->getModelClass('trans_unit'),
            'translation_class' => $storage->getModelClass('translation'),
        );

        $form = $this->createForm(new TransUnitType(), $transUnit, $options);

        if ($this->get('request')->getMethod() == 'POST') {
            $form->bind($this->get('request'));

            if ($form->isValid()) {
                $translations = $transUnit->filterNotBlankTranslations(); // only keep translations with a content

                // link new translations to a file to be able to export them.
                foreach ($translations as $translation) {
                    if (!$translation->getFile()) {
                        $file = $this->get('lexik_translation.file.manager')->getFor(
                            sprintf('%s.%s.yml', $transUnit->getDomain(), $translation->getLocale()),  // @todo allow other format
                            $this->container->getParameter('kernel.root_dir').'/Resources/translations'
                        );

                        if ($file instanceof File) {
                            $translation->setFile($file);
                        }
                    }
                }

                $transUnit->setTranslations($translations);
                $storage->persist($transUnit);
                $storage->flush();

                return $this->redirect($this->generateUrl('lexik_translation_grid'));
            }
        }

        return $this->render('LexikTranslationBundle:Edition:new.html.twig', array(
            'layout' => $this->container->getParameter('lexik_translation.base_layout'),
            'form'   => $form->createView(),
        ));
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
