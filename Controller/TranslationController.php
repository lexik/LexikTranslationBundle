<?php

namespace Lexik\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Model\File;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Form\TransUnitType;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationController extends Controller
{
    /**
     * Display the translation grid.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gridAction()
    {
        return $this->render('LexikTranslationBundle:Translation:grid.html.twig', array(
            'layout'    => $this->container->getParameter('lexik_translation.base_layout'),
            'inputType' => $this->container->getParameter('lexik_translation.grid_input_type'),
            'locales'   => $this->getManagedLocales(),
        ));
    }

    /**
     * Remove cache files for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invalidateCacheAction()
    {
        $this->get('translator')->removeLocalesCacheFiles($this->getManagedLocales());

        $session = $this->get('session');

        $session->getFlashBag()->add('success', $this->get('translator')->trans('translations.cache_removed', array(), 'LexikTranslationBundle'));

        return $this->redirect($this->generateUrl('lexik_translation_grid'));
    }

    /**
     * Add a new trans unit with translation for managed locales.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        // @todo
        // - move the logic into a form handler service
        // - define form type as services

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
        return $this->container->getParameter('lexik_translation.managed_locales');
    }
}
