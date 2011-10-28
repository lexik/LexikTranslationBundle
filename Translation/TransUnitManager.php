<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManager;

use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Entity\Translation;

/**
 * Class to manage TransUnit entities.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitManager
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Returns a new TransUnit instance with new translations for each $locales.
     *
     * @param array $locales
     * @return Lexik\Bundle\TranslationBundle\Entity\TransUnit
     */
    public function newInstance($locales = array())
    {
        $transUnit = new TransUnit();

        foreach ($locales as $locale) {
            $translation = new Translation();
            $translation->setLocale($locale);

            $transUnit->addTranslation($translation);
        }

        return $transUnit;
    }

    /**
     * Create a new trans unit.
     *
     * @param string $keyName
     * @param string $domainName
     * @param boolean $flush
     * @return Lexik\Bundle\TranslationBundle\Entity\TransUnit
     */
    public function create($keyName, $domainName, $flush = false)
    {
        $transUnit = new TransUnit();
        $transUnit->setKey($keyName);
        $transUnit->setDomain($domainName);

        $this->entityManager->persist($transUnit);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $transUnit;
    }

    /**
     * Add a new translation to the given trans unit.
     *
     * @param Lexik\Bundle\TranslationBundle\Entity\TransUnit $transUnit
     * @param string $locale
     * @param string $content
     * @param boolean $flush
     * @return Lexik\Bundle\TranslationBundle\Entity\Translation
     */
    public function addTranslation(TransUnit $transUnit, $locale, $content, $flush = false)
    {
        $translation = null;

        if(!$transUnit->hasTranslation($locale)) {
            $translation = new Translation();
            $translation->setTransUnit($transUnit);
            $translation->setLocale($locale);
            $translation->setContent($content);

            $transUnit->addTranslation($translation);

            $this->entityManager->persist($translation);

            if ($flush) {
                $this->entityManager->flush();
            }
        }

        return $translation;
    }

    /**
     * Update the translated content of a trans unit for the given locale.
     *
     * @param Lexik\Bundle\TranslationBundle\Entity\TransUnit $transUnit
     * @param string $locale
     * @param string $content
     * @param boolean $flush
     * @return Lexik\Bundle\TranslationBundle\Entity\Translation
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
            $this->entityManager->flush();
        }

        return $translation;
    }
}