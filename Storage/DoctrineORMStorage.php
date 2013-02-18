<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Lexik\Bundle\TranslationBundle\Model\File;
use Lexik\Bundle\TranslationBundle\Entity\FileRepository;
use Lexik\Bundle\TranslationBundle\Entity\TransUnitRepository;

use Doctrine\ORM\EntityManager;

/**
 * Doctrine ORM storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineORMStorage implements StorageInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $classes;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param array         $classes
     */
    public function __construct(EntityManager $em, array $classes)
    {
        $this->em = $em;
        $this->classes = $classes;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        $this->em->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($entity = null)
    {
         $this->em->flush($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entityName = null)
    {
         $this->em->clear($entityName);
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
    public function getFileByHash($hash)
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
    public function getTransUnitById($id)
    {
        return $this->getTransUnitRepository()->findOneById($id);
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
    public function getTranslationsFromFile(File $file, $onlyUpdated)
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
        return $this->em->getRepository($this->classes['trans_unit']);
    }

    /**
     * Returns the File repository.
     *
     * @return FileRepository
     */
    protected function getFileRepository()
    {
        return $this->em->getRepository($this->classes['file']);
    }
}
