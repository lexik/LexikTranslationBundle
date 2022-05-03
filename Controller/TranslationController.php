<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Lexik\Bundle\TranslationBundle\Form\Handler\TransUnitFormHandler;
use Lexik\Bundle\TranslationBundle\Form\Type\TransUnitType;
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Translation\Translator;
use Lexik\Bundle\TranslationBundle\Util\Csrf\CsrfCheckerTrait;
use Lexik\Bundle\TranslationBundle\Util\Overview\StatsAggregator;
use Lexik\Bundle\TranslationBundle\Util\Profiler\TokenFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationController extends AbstractController
{
    use CsrfCheckerTrait;

    private $translationStorage;

    private $statsAggregator;

    private $tokenFinder;

    private $transUnitFormHandler;

    private $lexikTranslator;

    private $translator;

    private $localeManager;

    public function __construct(
        StorageInterface $translationStorage,
        StatsAggregator $statsAggregator,
        TransUnitFormHandler $transUnitFormHandler,
        Translator $lexikTranslator,
        TranslatorInterface $translator,
        LocaleManagerInterface $localeManager,
        ?TokenFinder $tokenFinder
    ) {
        $this->translationStorage = $translationStorage;
        $this->statsAggregator = $statsAggregator;
        $this->transUnitFormHandler = $transUnitFormHandler;
        $this->lexikTranslator = $lexikTranslator;
        $this->translator = $translator;
        $this->localeManager = $localeManager;
        $this->tokenFinder = $tokenFinder;
    }

    /**
     * Display an overview of the translation status per domain.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function overviewAction()
    {
        $stats = $this->statsAggregator->getStats();

        return $this->render('@LexikTranslation/Translation/overview.html.twig', array(
            'layout'         => $this->getParameter('lexik_translation.base_layout'),
            'locales'        => $this->getManagedLocales(),
            'domains'        => $this->translationStorage->getTransUnitDomains(),
            'latestTrans'    => $this->translationStorage->getLatestUpdatedAt(),
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
        if ($this->getParameter('lexik_translation.dev_tools.enable') && $this->tokenFinder !== null) {
            $tokens = $this->tokenFinder->find();
        }

        return $this->render('@LexikTranslation/Translation/grid.html.twig', array(
            'layout'         => $this->getParameter('lexik_translation.base_layout'),
            'inputType'      => $this->getParameter('lexik_translation.grid_input_type'),
            'autoCacheClean' => $this->getParameter('lexik_translation.auto_cache_clean'),
            'toggleSimilar'  => $this->getParameter('lexik_translation.grid_toggle_similar'),
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
        $this->lexikTranslator->removeLocalesCacheFiles($this->getManagedLocales());

        $message = $this->translator->trans('translations.cache_removed', array(), 'LexikTranslationBundle');

        if ($request->isXmlHttpRequest()) {
            $this->checkCsrf();

            return new JsonResponse(array('message' => $message));
        }

        $request->getSession()->getFlashBag()->add('success', $message);

        return $this->redirect($this->generateUrl('lexik_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(TransUnitType::class, $this->transUnitFormHandler->createFormData(), $this->transUnitFormHandler->getFormOptions());

        if ($this->transUnitFormHandler->process($form, $request)) {
            $message = $this->translator->trans('translations.successfully_added', array(), 'LexikTranslationBundle');

            $request->getSession()->getFlashBag()->add('success', $message);

            $redirectUrl = $form->get('save_add')->isClicked() ? 'lexik_translation_new' : 'lexik_translation_grid';

            return $this->redirect($this->generateUrl($redirectUrl));
        }

        return $this->render('@LexikTranslation/Translation/new.html.twig', array(
            'layout' => $this->getParameter('lexik_translation.base_layout'),
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
        return $this->localeManager->getLocales();
    }
}
