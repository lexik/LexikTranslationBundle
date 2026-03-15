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
