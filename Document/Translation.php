<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Lexik\Bundle\TranslationBundle\Model\Translation as TranslationModel;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends TranslationModel
{
    /**
     * Convert all MongoTimestamp object to time.
     *
     */
    public function convertMongoTimestamp()
    {
        $this->createdAt = ($this->createdAt instanceof \MongoTimestamp) ? $this->createdAt->sec : $this->createdAt;;
        $this->updatedAt = ($this->updatedAt instanceof \MongoTimestamp) ? $this->updatedAt->sec : $this->updatedAt;
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.Translation::prePersist()
     */
    public function prePersist()
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.Translation::preUpdate()
     */
    public function preUpdate()
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}