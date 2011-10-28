<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Lexik\Bundle\TranslationBundle\Entity\TransUnit;

/**
 * @ORM\Entity
 * @ORM\Table(name="lexik_trans_unit_translations")
 * @ORM\HasLifecycleCallbacks
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Lexik\Bundle\TranslationBundle\Entity\TransUnit", inversedBy="translations", cascade={ "all" })
     * @ORM\JoinColumn(name="trans_unit_id", referencedColumnName="id")
     */
    protected $transUnit;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=10, name="locale")
     */
    protected $locale;

    /**
     * @ORM\Column(type="text", name="content")
     *
     * @Assert\NotBlank(groups={"contentNotBlank"})
     */
    protected $content;

    /**
     * @ORM\Column(type="datetime", name="created_at", nullable="true")
     *
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at", nullable="true")
     *
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
     * @param Lexik\Bundle\TranslationBundle\Entity\TransUnit $transUnit
     */
    public function setTransUnit(TransUnit $transUnit)
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
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}