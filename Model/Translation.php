<?php

namespace Lexik\Bundle\TranslationBundle\Model;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class represent a translation for a given locale of a TransUnit object.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class Translation
{
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
     * @var File
     */
    protected $file;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime
     */
    protected $updatedAt;

    /**
     * @var boolean
     */
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
     * Set file
     *
     * @param File $file
     */
    public function setFile(File $file)
    {
        $this->file = $file;
    }

    /**
     * Get file
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
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
