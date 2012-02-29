<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;

/**
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
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.Translation::prePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.Translation::preUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}