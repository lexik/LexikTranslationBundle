<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;
use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends TranslationModel implements TranslationInterface
{
    // Relationship mapping is defined in XML: Resources/config/doctrine/Translation.mongodb-odm.xml
    protected FileInterface $file;

    /**
     * @deprecated No longer needed since the legacy mongo extension is no longer supported.
     */
    public function convertMongoTimestamp(): void
    {
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
