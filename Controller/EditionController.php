<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

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
        $repository = $this->get('lexik_translation.storage_manager')->getRepository($this->container->getParameter('lexik_translation.trans_unit.class'));

        $transUnits = $repository->getTransUnitList(
            $locales,
            $this->get('request')->query->get('rows', 20),
            $this->get('request')->query->get('page', 1),
            $this->get('request')->query->all()
        );

        $jqGridMapper = new Mapper(
            $this->get('request'),
            $transUnits,
            $repository->count($locales, $this->get('request')->query->all())
        );

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
            'layout' => $this->container->getParameter('lexik_translation.base_layout'),
            'locales' => $this->getManagedLocales(),
        ));
    }

    /**
     * Update a trans unit element from the javascript grid.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction()
    {
        $request = $this->get('request');
        if ($request->isXmlHttpRequest()) {
            $result = array();

            if ('edit' == $request->request->get('oper')) {
                $this->get('translator')->updateTransUnitFromRequest($this->get('request'));
                $result['success'] = true;
            }

            return new Response(json_encode($result));
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Remove cache files for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invalidateCacheAction()
    {
        $this->get('translator')->removeLocalesCacheFiles($this->getManagedLocales());

        $this->get('session')->setFlash('success', $this->get('translator')->trans('translations.cache_removed', array(), 'LexikTranslationBundle'));

        return $this->redirect($this->generateUrl('lexik_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        $em = $this->get('lexik_translation.storage_manager');
        $transUnit = $this->get('lexik_translation.trans_unit.manager')->newInstance($this->getManagedLocales());
        $domains = $em->getRepository('LexikTranslationBundle:TransUnit')->getAllDomains();

        $form = $this->createForm(new TransUnitType($domains), $transUnit);

        if ($this->get('request')->getMethod() == 'POST') {
            $form->bindRequest($this->get('request'));

            if ($form->isValid()) {
                $translations = $transUnit->filterNotBlankTranslations(); // only keep translations with a content

                $transUnit->setTranslations($translations);
                $em->persist($transUnit);
                $em->flush();

                return $this->redirect($this->generateUrl('lexik_translation_grid'));
            }
        }

        return $this->render('LexikTranslationBundle:Edition:new.html.twig', array(
            'layout' => $this->container->getParameter('lexik_translation.base_layout'),
            'form' => $form->createView(),
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