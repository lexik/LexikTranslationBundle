<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use DateTime;
use Lexik\Bundle\TranslationBundle\Model\File as FileModel;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class File extends FileModel
{
    public function prePersist(): void
    {
        $now = new DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    public function preUpdate(): void
    {
        $now = new DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}
