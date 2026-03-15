<?php

namespace Lexik\Bundle\TranslationBundle\Model;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
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
    protected string $locale;

    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['contentNotBlank'])]
    protected $content;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $updatedAt;

    #[ORM\Column(name: 'modified_manually', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $modifiedManually = false;

    protected FileInterface $file;

    /**
     * Set locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->content = '';
    }

    /**
     * Get locale
     */
    public function getLocale(): string
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

    public function isModifiedManually(): bool
    {
        return $this->modifiedManually;
    }

    public function setModifiedManually(bool $modifiedManually): void
    {
        $this->modifiedManually = $modifiedManually;
    }

    public function setFile(FileInterface $file): void
    {
        $this->file = $file;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }
}
