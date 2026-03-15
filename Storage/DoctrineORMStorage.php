<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use DateTime;
use Doctrine\ORM\EntityManager;

/**
 * Doctrine ORM storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineORMStorage extends AbstractDoctrineStorage
{
    /**
     * Returns true if translation tables exist.
     */
    public function translationsTablesExist(): bool
    {
        /** @var EntityManager $em */
        $em = $this->getManager();

        try {
            $tables = [
                $em->getClassMetadata($this->getModelClass('trans_unit'))->getTableName(),
                $em->getClassMetadata($this->getModelClass('translation'))->getTableName(),
            ];

            return $em->getConnection()->createSchemaManager()->tablesExist($tables);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestUpdatedAt(): ?DateTime
    {
        return $this->getTranslationRepository()->getLatestTranslationUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTransUnitByDomains(): array
    {
        $results = $this->getTransUnitRepository()->countByDomains();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['domain']] = (int)$row['number'];
        }

        return $counts;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTranslationByLocales(string $domain): array
    {
        $results = $this->getTranslationRepository()->countByLocales($domain);

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['locale']] = (int)$row['number'];
        }

        return $counts;
    }

    /**
     * Returns the TransUnit repository.
     *
     * @return object
     */
    protected function getTranslationRepository()
    {
        return $this->getManager()->getRepository($this->classes['translation']);
    }
}
