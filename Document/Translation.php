<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends TranslationModel implements TranslationInterface
{
    // Relationship mapping is defined in XML: Resources/config/doctrine/Translation.mongodb-odm.xml
    protected $file;

    public function setFile($file): void
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }

    /**
     * Convert all MongoTimestamp object to time.
     */
    public function convertMongoTimestamp(): void
    {
        $this->createdAt = ($this->createdAt instanceof \MongoTimestamp) ? $this->createdAt->sec : $this->createdAt;
        $this->updatedAt = ($this->updatedAt instanceof \MongoTimestamp) ? $this->updatedAt->sec : $this->updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(): void
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(): void
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}
