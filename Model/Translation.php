<?php

namespace Lexik\Bundle\TranslationBundle\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class represent a translation for a given locale of a TransUnit object.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[ORM\MappedSuperclass]
abstract class Translation
{
    #[ORM\Column(name: 'locale', type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    protected $locale;

    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['contentNotBlank'])]
    protected $content;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $updatedAt;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'modified_manually', type: Types::BOOLEAN, nullable: true)]
    protected $modifiedManually = false;

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
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
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
     * @return bool
     */
    public function isModifiedManually()
    {
        return $this->modifiedManually;
    }

    /**
     * @param bool $modifiedManually
     */
    public function setModifiedManually($modifiedManually)
    {
        $this->modifiedManually = $modifiedManually;
    }
}
