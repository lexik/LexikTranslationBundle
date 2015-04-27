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
            $em->getClassMetadata($this->getModelClass('translation'))->table['name']
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
     * Returns the TransUnit repository.
     *
     * @return object
     */
    protected function getTranslationRepository()
    {
        return $this->getManager()->getRepository($this->classes['translation']);
    }
}
