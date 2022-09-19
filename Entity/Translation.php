<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use DateTime;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;

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
     * @param TransUnit $transUnit
     */
    public function setTransUnit(\Lexik\Bundle\TranslationBundle\Model\TransUnit $transUnit)
    {
        $this->transUnit = $transUnit;
    }

    /**
     * Get transUnit
     *
     * @return TransUnit
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
        $now             = new DateTime("now");
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function preUpdate()
    {
        $this->updatedAt = new DateTime("now");
    }
}
