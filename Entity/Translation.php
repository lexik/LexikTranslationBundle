<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use DateTime;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;

/**
 * @UniqueEntity(fields={"transUnit", "locale"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends TranslationModel implements TranslationInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var TransUnit
     */
    protected TransUnit $transUnit;

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
    public function prePersist(): void
    {
        $now             = new DateTime("now");
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime("now");
    }
}
