<?php

namespace Lexik\Bundle\TranslationBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * This class represent a translation for a given locale of a TransUnit object.
 *
 * @UniqueEntity(fields={"transUnit", "locale"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class Translation
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Lexik\Bundle\TranslationBundle\Model\TransUnit
     */
    protected $transUnit;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    protected $locale;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"contentNotBlank"})
     */
    protected $content;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        $this->content = '';
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set transUnit
     *
     * @param Lexik\Bundle\TranslationBundle\Model\TransUnit $transUnit
     */
    public function setTransUnit(\Lexik\Bundle\TranslationBundle\Model\TransUnit $transUnit)
    {
        $this->transUnit = $transUnit;
    }

    /**
     * Get transUnit
     *
     * @return Lexik\Bundle\TranslationBundle\Model\TransUnit
     */
    public function getTransUnit()
    {
        return $this->transUnit;
    }

    /**
     * Get createdAt
     *
     * @return datetime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Things to do on prePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * Things to do on preUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}