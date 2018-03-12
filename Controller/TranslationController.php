<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Util\Csrf\CsrfCheckerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationController extends Controller
{
    use CsrfCheckerTrait;

    /**
     * Display an overview of the translation status per domain.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function overviewAction()
    {
        /** @var StorageInterface $storage */
        $storage = $this->get('lexik_translation.translation_storage');

        $stats = $this->get('lexik_translation.overview.stats_aggregator')->getStats();

        return $this->render('LexikTranslationBundle:Translation:overview.html.twig', array(
            'layout'         => $this->container->getParameter('lexik_translation.base_layout'),
            'locales'        => $this->getManagedLocales(),
            'domains'        => $storage->getTransUnitDomains(),
            'latestTrans'    => $storage->getLatestUpdatedAt(),
            'stats'          => $stats,
        ));
    }

    /**
     * Display the translation grid.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gridAction()
    {
        $tokens = null;
        if ($this->container->getParameter('lexik_translation.dev_tools.enable')) {
            $tokens = $this->get('lexik_translation.token_finder')->find();
        }

        return $this->render('LexikTranslationBundle:Translation:grid.html.twig', array(
            'layout'         => $this->container->getParameter('lexik_translation.base_layout'),
            'inputType'      => $this->container->getParameter('lexik_translation.grid_input_type'),
            'autoCacheClean' => $this->container->getParameter('lexik_translation.auto_cache_clean'),
            'toggleSimilar'  => $this->container->getParameter('lexik_translation.grid_toggle_similar'),
            'locales'        => $this->getManagedLocales(),
            'tokens'         => $tokens,
        ));
    }

    /**
     * Remove cache files for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invalidateCacheAction(Request $request)
    {
        $this->get('lexik_translation.translator')->removeLocalesCacheFiles($this->getManagedLocales());

        $message = $this->get('translator')->trans('translations.cache_removed', array(), 'LexikTranslationBundle');

        if ($request->isXmlHttpRequest()) {
            $this->checkCsrf();

            return new JsonResponse(array('message' => $message));
        }

        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->redirect($this->generateUrl('lexik_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $handler = $this->get('lexik_translation.form.handler.trans_unit');

        $form = $this->createForm('Lexik\Bundle\TranslationBundle\Form\Type\TransUnitType', $handler->createFormData(), $handler->getFormOptions());

        if ($handler->process($form, $request)) {
            $message = $this->get('translator')->trans('translations.successfully_added', array(), 'LexikTranslationBundle');

            $this->get('session')->getFlashBag()->add('success', $message);

            $redirectUrl = $form->get('save_add')->isClicked() ? 'lexik_translation_new' : 'lexik_translation_grid';

            return $this->redirect($this->generateUrl($redirectUrl));
        }

        return $this->render('LexikTranslationBundle:Translation:new.html.twig', array(
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
        return $this->get('lexik_translation.locale.manager')->getLocales();
    }
}
