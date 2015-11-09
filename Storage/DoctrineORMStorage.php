<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

/**
 * Doctrine ORM storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineORMStorage extends AbstractDoctrineStorage
{
    /**
     * Returns true if translation tables exist.
     *
     * @return boolean
     */
    public function translationsTablesExist()
    {
        $em = $this->getManager();

        $tables = array(
            $em->getClassMetadata($this->getModelClass('trans_unit'))->table['name'],
            $em->getClassMetadata($this->getModelClass('translation'))->table['name'],
        );

        $schemaManager = $em->getConnection()->getSchemaManager();

        return $schemaManager->tablesExist($tables);
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestUpdatedAt()
    {
        return $this->getTranslationRepository()->getLatestTranslationUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTransUnitByDomains()
    {
        $results = $this->getTransUnitRepository()->countByDomains();

        $counts = array();
        foreach ($results as $row) {
            $counts[$row['domain']] = (int) $row['number'];
        }

        return $counts;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTranslationByLocales($domain)
    {
        $results = $this->getTranslationRepository()->countByLocales($domain);

        $counts = array();
        foreach ($results as $row) {
            $counts[$row['locale']] = (int) $row['number'];
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
