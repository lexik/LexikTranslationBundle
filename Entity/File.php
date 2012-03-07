<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Lexik\Bundle\TranslationBundle\Model\File as FileModel;

/**
 * @UniqueEntity(fields={"hash"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class File extends FileModel
{
    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.File::prePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.File::preUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}