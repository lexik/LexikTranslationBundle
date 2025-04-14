<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Lexik\Bundle\TranslationBundle\Model\Translation;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Lexik\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * @UniqueEntity(fields={"key", "domain"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel implements TransUnitInterface
{
    /**
     * Add translations
     *
     * @param \Lexik\Bundle\TranslationBundle\Entity\Translation $translations
     */
    public function addTranslation(Translation $translation): void
    {
        $translation->setTransUnit($this);

        $this->translations[] = $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime("now");
    }
}
