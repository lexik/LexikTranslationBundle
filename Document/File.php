<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Lexik\Bundle\TranslationBundle\Model\File as FileModel;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use DateTime;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class File extends FileModel implements FileInterface
{
    /**
     * {@inheritdoc}
     */
    public function prePersist(): void
    {
        $now = new DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(): void
    {
        $now = new DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}
