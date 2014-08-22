<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Lexik\Bundle\TranslationBundle\Model\File as FileModel;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;

/**
 * @UniqueEntity(fields={"hash"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class File extends FileModel implements FileInterface
{
    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}
