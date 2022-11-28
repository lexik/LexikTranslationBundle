<?php

namespace Lexik\Bundle\TranslationBundle\Form\Handler;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Lexik\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Propel\TransUnit as PropelTransUnit;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitFormHandler implements FormHandlerInterface
{
    /**
     * @param string $rootDir
     */
    public function __construct(
        protected TransUnitManagerInterface $transUnitManager,
        protected FileManagerInterface $fileManager,
        protected StorageInterface $storage,
        protected LocaleManagerInterface $localeManager,
        protected string $rootDir,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createFormData()
    {
        return $this->transUnitManager->newInstance($this->localeManager->getLocales());
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [
            'domains'           => $this->storage->getTransUnitDomains(),
            'data_class'        => $this->storage->getModelClass('trans_unit'),
            'translation_class' => $this->storage->getModelClass('translation'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(FormInterface $form, Request $request)
    {
        $valid = false;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $transUnit = $form->getData();
                $translations = $transUnit->filterNotBlankTranslations(); // only keep translations with a content

                // link new translations to a file to be able to export them.
                foreach ($translations as $translation) {
                    if (!$translation->getFile()) {
                        $file = $this->fileManager->getFor(
                            sprintf('%s.%s.yml', $transUnit->getDomain(), $translation->getLocale()),
                            $this->rootDir . '/Resources/translations'
                        );

                        if ($file instanceof FileInterface) {
                            $translation->setFile($file);
                        }
                    }
                }

                if ($transUnit instanceof PropelTransUnit) {
                    // The setTranslations() method only accepts PropelCollections
                    $translations = new \PropelObjectCollection($translations);
                }

                $transUnit->setTranslations($translations);

                $this->storage->persist($transUnit);
                $this->storage->flush();

                $valid = true;
            }
        }

        return $valid;
    }
}
