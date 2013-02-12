<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

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
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var string
     */
    private $transUnitclass;

    /**
     * @var string
     */
    private $translationClass;

    /**
     * Csontruct.
     *
     * @param ObjectManager $objectManager
     * @param string $transUnitclass
     * @param string $translationClass
     */
    public function __construct(ObjectManager $objectManager, $transUnitclass, $translationClass)
    {
        $this->objectManager = $objectManager;
        $this->transUnitclass = $transUnitclass;
        $this->translationClass = $translationClass;
    }

    /**
     * {@inheritdoc}
     */
    public function newInstance($locales = array())
    {
        $class = $this->transUnitclass;
        $transUnit = new $class();

        foreach ($locales as $locale) {
            $class = $this->translationClass;
            $translation = new $class();
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

        $this->objectManager->persist($transUnit);

        if ($flush) {
            $this->objectManager->flush();
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByKeyAndDomain($key, $domain)
    {
        $key = mb_substr($key, 0, 255, 'UTF-8');

        $fields = array(
            'key'    => $key,
            'domain' => $domain,
        );

        return $this->objectManager
            ->getRepository($this->transUnitclass)
            ->findOneBy($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(TransUnit $transUnit, $locale, $content, File $file = null, $flush = false)
    {
        $translation = null;

        if(!$transUnit->hasTranslation($locale)) {
            $class = $this->translationClass;

            $translation = new $class();
            $translation->setLocale($locale);
            $translation->setContent($content);

            if ($file != null) {
                $translation->setFile($file);
            }

            $transUnit->addTranslation($translation);

            $this->objectManager->persist($translation);

            if ($flush) {
                $this->objectManager->flush();
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
            $this->objectManager->flush();
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
                    $this->addTranslation($transUnit, $locale, $content);
                }
            }
        }

        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationsFromFile(File $file, $onlyUpdated)
    {
        return $this->objectManager
            ->getRepository($this->transUnitclass)
            ->getTranslationsForFile($file, $onlyUpdated);
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        return $this->objectManager
            ->getRepository($this->transUnitclass)
            ->findOneById($id);
    }
}
