<?php

namespace Lexik\Bundle\TranslationBundle\Form\Handler;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Model\File;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitFormHandler implements FormHandlerInterface
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;

    /**
     * @var FileManagerInterface
     */
    protected $fileManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var array
     */
    protected $managedLoales;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param FileManagerInterface      $fileManager
     * @param StorageInterface          $storage
     * @param array                     $managedLoales
     * @param string                    $rootDir
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, FileManagerInterface $fileManager, StorageInterface $storage, array $managedLoales, $rootDir)
    {
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
        $this->storage = $storage;
        $this->managedLoales = $managedLoales;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function createFormData()
    {
        return $this->transUnitManager->newInstance($this->managedLoales);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return array(
            'domains'           => $this->storage->getTransUnitDomains(),
            'data_class'        => $this->storage->getModelClass('trans_unit'),
            'translation_class' => $this->storage->getModelClass('translation'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(FormInterface $form, Request $request)
    {
        $valid = false;

        if ($request->isMethod('POST')) {
            $form->submit($request);

            if ($form->isValid()) {
                $transUnit = $form->getData();
                $translations = $transUnit->filterNotBlankTranslations(); // only keep translations with a content

                // link new translations to a file to be able to export them.
                foreach ($translations as $translation) {
                    if (!$translation->getFile()) {
                        $file = $this->fileManager->getFor(
                            sprintf('%s.%s.yml', $transUnit->getDomain(), $translation->getLocale()),  // @todo allow other format
                            $this->rootDir.'/Resources/translations'
                        );

                        if ($file instanceof File) {
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
