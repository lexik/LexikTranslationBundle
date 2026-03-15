<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;
use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[HasLifecycleCallbacks]
#[UniqueEntity(fields: ['transUnit', 'locale'])]
class Translation extends TranslationModel implements TranslationInterface
{
    protected int $id;

    // Relationship mappings are defined in XML: Resources/config/doctrine/Translation.orm.xml
    protected $transUnit;

    protected FileInterface $file;

    // modifiedManually is inherited from TranslationModel

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set transUnit
     *
     * @param TransUnit $transUnit
     */
    public function setTransUnit(TransUnit $transUnit): void
    {
        $this->transUnit = $transUnit;
    }

    /**
     * Get transUnit
     *
     * @return TransUnit
     */
    public function getTransUnit(): TransUnit
    {
        return $this->transUnit;
    }

    /**
     * {@inheritdoc}
     */
    #[PrePersist]
    public function prePersist(): void
    {
        $now = new DateTime("now");
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime("now");
    }
}
