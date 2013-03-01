<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Lexik\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;

/**
 * @UniqueEntity(fields={"key", "domain"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel
{
    /**
     * Add translations
     *
     * @param Lexik\Bundle\TranslationBundle\Entity\Translation $translations
     */
    public function addTranslation(\Lexik\Bundle\TranslationBundle\Model\Translation $translation)
    {
        $translation->setTransUnit($this);

        $this->translations[] = $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}
