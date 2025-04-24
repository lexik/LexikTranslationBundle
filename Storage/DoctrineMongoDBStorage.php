<?php

namespace Lexik\Bundle\TranslationBundle\Storage;
use Datetime;

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
    public function getLatestUpdatedAt(): ?DateTime
    {
        return $this->getTransUnitRepository()->getLatestTranslationUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTransUnitByDomains(): array 
    {
        return $this->getTransUnitRepository()->countByDomains();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTranslationByLocales(string $domain):array 
    {
        return $this->getTransUnitRepository()->countTranslationsByLocales($domain);
    }
}
