<?php

namespace Lexik\Bundle\TranslationBundle\Form\Handler;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Lexik\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[AsAlias(id: 'lexik_translation.form.handler.trans_unit', public: true)]
class TransUnitFormHandler implements FormHandlerInterface
{
    /**
     * @param string $rootDir
     */
    public function __construct(
        #[Autowire(service: 'lexik_translation.trans_unit.manager')]
        protected TransUnitManagerInterface $transUnitManager,
        #[Autowire(service: 'lexik_translation.file.manager')]
        protected FileManagerInterface $fileManager,
        #[Autowire(service: 'lexik_translation.translation_storage')]
        protected StorageInterface $storage,
        #[Autowire(service: 'Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface')]
        protected LocaleManagerInterface $localeManager,
        #[Autowire('%kernel.project_dir%')]
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

                $transUnit->setTranslations($translations);

                $this->storage->persist($transUnit);
                $this->storage->flush();

                $valid = true;
            }
        }

        return $valid;
    }
}
