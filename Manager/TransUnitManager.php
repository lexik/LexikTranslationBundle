<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Model\File;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Model\Translation;

/**
 * Class to manage TransUnit entities or documents.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitManager implements TransUnitManagerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    /**
     * @var String
     */
    private $kernelRootDir;

    /**
     * Csontruct.
     *
     * @param StorageInterface $storage
     * @param FileManagerInterface $fm
     * @param String $kernelRootDir
     */
    public function __construct(StorageInterface $storage, FileManagerInterface $fm, $kernelRootDir)
    {
        $this->storage = $storage;
        $this->fileManager = $fm;
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function newInstance($locales = array())
    {
        $transUnitClass = $this->storage->getModelClass('trans_unit');
        $translationClass = $this->storage->getModelClass('translation');

        $transUnit = new $transUnitClass();

        foreach ($locales as $locale) {
            $translation = new $translationClass();
            $translation->setLocale($locale);

            $transUnit->addTranslation($translation);
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function create($keyName, $domainName, $flush = false)
    {
        $transUnit = $this->newInstance();
        $transUnit->setKey($keyName);
        $transUnit->setDomain($domainName);

        $this->storage->persist($transUnit);

        if ($flush) {
            $this->storage->flush();
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(TransUnit $transUnit, $locale, $content, File $file = null, $flush = false)
    {
        $translation = null;

        if(!$transUnit->hasTranslation($locale)) {
            $class = $this->storage->getModelClass('translation');

            $translation = new $class();
            $translation->setLocale($locale);
            $translation->setContent($content);

            if ($file != null) {
                $translation->setFile($file);
            }

            $transUnit->addTranslation($translation);

            $this->storage->persist($translation);

            if ($flush) {
                $this->storage->flush();
            }
        }

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslation(TransUnit $transUnit, $locale, $content, $flush = false)
    {
        $translation = null;
        $i = 0;
        $end = $transUnit->getTranslations()->count();
        $found = false;

        while ($i<$end && !$found) {
            $found = ($transUnit->getTranslations()->get($i)->getLocale() == $locale);
            $i++;
        }

        if ($found) {
            $translation = $transUnit->getTranslations()->get($i-1);
            $translation->setContent($content);
        }

        if ($flush) {
            $this->storage->flush();
        }

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslationsContent(TransUnit $transUnit, array $translations, $flush = false)
    {
        foreach ($translations as $locale => $content) {
            if (!empty($content)) {
                if ($transUnit->hasTranslation($locale)) {
                    $this->updateTranslation($transUnit, $locale, $content);
                } else {
                    //We need to get a proper file for this translation
                    $file = $this->getTranslationFile($transUnit, $locale);
                    $this->addTranslation($transUnit, $locale, $content, $file);
                }
            }
        }

        if ($flush) {
            $this->storage->flush();
        }
    }

    /**
     * Get the proper File for this TransUnit and locale
     *
     * @param TransUnit $transUnit
     * @param string $locale
     *
     * @return File|null
     */
    protected function getTranslationFile(TransUnit &$transUnit, $locale)
    {
        $file=null;
        foreach ($transUnit->getTranslations() as $translationModel) {
            if (null !== $file = $translationModel->getFile()) {
                break;
            }
        }

        //if we found a file
        if ($file!=null) {
            //make sure we got the correct file for this locale and domain
            $name = sprintf('%s.%s.%s', $file->getDomain(), $locale, $file->getExtention());
            $file = $this->fileManager->getFor($name, $this->kernelRootDir.DIRECTORY_SEPARATOR.$file->getPath());
        }

        return $file;
    }
}
