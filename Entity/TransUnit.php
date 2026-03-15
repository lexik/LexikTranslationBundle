<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Lexik\Bundle\TranslationBundle\Document\Translation as DocumentTranslation;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;
use Lexik\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[HasLifecycleCallbacks]
#[UniqueEntity(fields: ['key', 'domain'])]
class TransUnit extends TransUnitModel implements TransUnitInterface
{
    protected $id;

    // translations property is inherited from TransUnitModel
    // Relationship mapping is defined in XML: Resources/config/doctrine/TransUnit.orm.xml
    protected Collection $translations;

    /**
     * Add translations
     */
    public function addTranslation(DocumentTranslation|Translation $translation): void
    {
        $translation->setTransUnit(transUnit: $this);

        $this->translations[] = $translation;
    }

    /**
     * {@inheritdoc}
     */
    #[PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new DateTime(datetime: "now");
        $this->updatedAt = new DateTime(datetime: "now");
    }

    /**
     * {@inheritdoc}
     */
    #[PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime("now");
    }
}
