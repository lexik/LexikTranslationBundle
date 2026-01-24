<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Lexik\Bundle\TranslationBundle\Form\Handler\TransUnitFormHandler;
use Lexik\Bundle\TranslationBundle\Form\Type\TransUnitType;
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Translation\TranslatorDecorator;
use Lexik\Bundle\TranslationBundle\Util\Csrf\CsrfCheckerTrait;
use Lexik\Bundle\TranslationBundle\Util\Overview\StatsAggregator;
use Lexik\Bundle\TranslationBundle\Util\Profiler\TokenFinder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationController extends AbstractController
{
    use CsrfCheckerTrait;

    public function __construct(private readonly StorageInterface $translationStorage, private readonly StatsAggregator $statsAggregator, private readonly TransUnitFormHandler $transUnitFormHandler, private readonly TranslatorInterface $lexikTranslator, private readonly TranslatorInterface $translator, private readonly LocaleManagerInterface $localeManager, private readonly ?TokenFinder $tokenFinder)
    {
    }

    /**
     * Display an overview of the translation status per domain.
     */
    public function overviewAction(): Response
    {
        $stats = $this->statsAggregator->getStats();

        return $this->render('@LexikTranslation/Translation/overview.html.twig', ['layout'         => $this->getParameter('lexik_translation.base_layout'), 'locales'        => $this->getManagedLocales(), 'domains'        => $this->translationStorage->getTransUnitDomains(), 'latestTrans'    => $this->translationStorage->getLatestUpdatedAt(), 'stats'          => $stats]);
    }

    /**
     * Display the translation grid.
     */
    public function gridAction(): Response
    {
        $tokens = null;
        if ($this->getParameter('lexik_translation.dev_tools.enable') && $this->tokenFinder !== null) {
            $tokens = $this->tokenFinder->find();
        }

        return $this->render('@LexikTranslation/Translation/grid.html.twig', ['layout'         => $this->getParameter('lexik_translation.base_layout'), 'inputType'      => $this->getParameter('lexik_translation.grid_input_type'), 'autoCacheClean' => $this->getParameter('lexik_translation.auto_cache_clean'), 'toggleSimilar'  => $this->getParameter('lexik_translation.grid_toggle_similar'), 'locales'        => $this->getManagedLocales(), 'tokens'         => $tokens]);
    }

    /**
     * Remove cache files for managed locales.
     */
    public function invalidateCacheAction(Request $request): Response
    {
        if (method_exists($this->lexikTranslator, 'removeLocalesCacheFiles')) {
            $this->lexikTranslator->removeLocalesCacheFiles($this->getManagedLocales());
        }

        $message = $this->translator->trans('translations.cache_removed', [], 'LexikTranslationBundle');

        if ($request->isXmlHttpRequest()) {
            $this->checkCsrf();

            return new JsonResponse(['message' => $message]);
        }

        $request->getSession()->getFlashBag()->add('success', $message);

        return $this->redirect($this->generateUrl('lexik_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     */
    public function newAction(Request $request): Response
    {
        $form = $this->createForm(TransUnitType::class, $this->transUnitFormHandler->createFormData(), $this->transUnitFormHandler->getFormOptions());

        if ($this->transUnitFormHandler->process($form, $request)) {
            $message = $this->translator->trans('translations.successfully_added', [], 'LexikTranslationBundle');

            $request->getSession()->getFlashBag()->add('success', $message);

            $redirectUrl = $form->get('save_add')->isClicked() ? 'lexik_translation_new' : 'lexik_translation_grid';

            return $this->redirect($this->generateUrl($redirectUrl));
        }

        return $this->render('@LexikTranslation/Translation/new.html.twig', ['layout' => $this->getParameter('lexik_translation.base_layout'), 'form'   => $form->createView()]);
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
