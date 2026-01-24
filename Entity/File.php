<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Lexik\Bundle\TranslationBundle\Model\File as FileModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[HasLifecycleCallbacks]
#[UniqueEntity(fields: ['hash'])]
class File extends FileModel implements FileInterface
{
    protected $id;

    // translations property is inherited from FileModel
    // Relationship mapping is defined in XML: Resources/config/doctrine/File.orm.xml
    protected Collection $translations;

    /**
     * {@inheritdoc}
     */
    #[PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * {@inheritdoc}
     */
    #[PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime("now");
    }
}
