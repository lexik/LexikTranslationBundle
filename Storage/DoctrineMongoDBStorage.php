<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

/**
 * Doctrine MongoDB storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineMongoDBStorage extends AbstractDoctrineStorage
{
    /**
     * {@inheritdoc}
     */
    public function getLatestUpdatedAt()
    {
        return $this->getTransUnitRepository()->getLatestTranslationUpdatedAt();
    }
}
