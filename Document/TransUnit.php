<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Lexik\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel implements TransUnitInterface
{
    /**
     * Convert all MongoTimestamp object to time.
     */
    public function convertMongoTimestamp()
    {
        $this->createdAt = ($this->createdAt instanceof \MongoTimestamp) ? $this->createdAt->sec : $this->createdAt;;
        $this->updatedAt = ($this->updatedAt instanceof \MongoTimestamp) ? $this->updatedAt->sec : $this->updatedAt;

        foreach ($this->getTranslations() as $translation) {
            $translation->convertMongoTimestamp();
        }
    }


    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}
