<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;

/**
 * @UniqueEntity(fields={"transUnit", "locale"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends TranslationModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Lexik\Bundle\TranslationBundle\Entity\TransUnit
     */
    protected $transUnit;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transUnit
     *
     * @param Lexik\Bundle\TranslationBundle\Entity\TransUnit $transUnit
     */
    public function setTransUnit(\Lexik\Bundle\TranslationBundle\Model\TransUnit $transUnit)
    {
        $this->transUnit = $transUnit;
    }

    /**
     * Get transUnit
     *
     * @return Lexik\Bundle\TranslationBundle\Entity\TransUnit
     */
    public function getTransUnit()
    {
        return $this->transUnit;
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
