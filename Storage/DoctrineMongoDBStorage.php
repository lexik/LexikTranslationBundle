<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Lexik\Bundle\TranslationBundle\Document\TransUnitRepository;
use Lexik\Bundle\TranslationBundle\Document\FileRepository;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Doctrine MongoDB storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineMongoDBStorage implements StorageInterface
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var array
     */
    private $classes;

    /**
     * Constructor.
     *
     * @param DocumentManager $dm
     * @param array           $classes
     */
    public function __construct(DocumentManager $dm, array $classes)
    {
        $this->dm = $dm;
        $this->classes = $classes;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        $this->dm->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($entity = null)
    {
         $this->dm->flush($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entityName = null)
    {
         $this->dm->clear($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesByLoalesAndDomains(array $locales, array $domains)
    {
        return $this->getFileRepository()->findForLoalesAndDomains($locales, $domains);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileByHash(string $hash)
    {
        return $this->getFileRepository()->findOneBy(array('hash' => $hash));
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitDomains()
    {
        return $this->getTransUnitRepository()->getAllDomains();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitDomainsByLocale()
    {
        return $this->getTransUnitRepository()->getAllDomainsByLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitsByLocaleAndDomain($locale, $domain)
    {
        return $this->getTransUnitRepository()->getAllByLocaleAndDomain($locale, $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        return $this->getTransUnitRepository()->getTransUnitList($locales, $rows, $page, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function countTransUnits(array $locales = null,  array $filters = null)
    {
        return $this->getTransUnitRepository()->count($locales, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationsFromFile(ModelFile $file, $onlyUpdated)
    {
        return $this->getTransUnitRepository()->getTranslationsForFile($file, $onlyUpdated);
    }

    /**
     * Returns the TransUnit repository.
     *
     * @return TransUnitRepository
     */
    protected function getTransUnitRepository()
    {
        return $this->dm->getRepository($this->classes['trans_unit']);
    }

    /**
     * Returns the File repository.
     *
     * @return FileRepository
     */
    protected function getFileRepository()
    {
        return $this->dm->getRepository($this->classes['file']);
    }
}
