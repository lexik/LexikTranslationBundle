<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Lexik\Bundle\TranslationBundle\Propel\FilePeer;
use Lexik\Bundle\TranslationBundle\Propel\FileQuery;
use Lexik\Bundle\TranslationBundle\Propel\FileRepository;
use Lexik\Bundle\TranslationBundle\Propel\TransUnitPeer;
use Lexik\Bundle\TranslationBundle\Propel\TransUnitQuery;
use Lexik\Bundle\TranslationBundle\Propel\TransUnitRepository;

/**
 * Doctrine ORM storage class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class PropelStorage implements StorageInterface
{
    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var PDO
     */
    private $connection;

    /**
     * @var array
     */
    private $classes;

    /**
     * @var array
     */
    private $collections = array();

    /**
     * @var TransUnitRepository
     */
    private $transUnitRepository;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * Constructor.
     *
     * @param string        $connection
     * @param array         $classes
     */
    public function __construct($connectionName, array $classes)
    {
        $this->connectionName = $connectionName;
        $this->classes = $classes;

        $this->initCollections();
    }

    private function initCollections()
    {
        $this->collections = array();

        foreach ($this->classes as $className) {
            $this->initCollection($className);
        }
    }

    private function initCollection($className)
    {
        $this->collections[$className] = new \PropelObjectCollection();
        $this->collections[$className]->setModel($className);
    }

    /**
     * @return PDO
     */
    private function getConnection()
    {
        if (null === $this->connection) {
            $this->connection = \Propel::getConnection($this->connectionName);
        }

        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        $found = false;

        foreach ($this->classes as $className) {
            if ($entity instanceof $className) {
                $this->collections[$className]->append($entity);
                $found = true;

                break;
            }
        }

        if (!$found) {
            throw new \RuntimeException(sprintf('Invalid entity class: "%s".', get_class($entity)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush($entity = null)
    {
        if (null === $entity) {
            foreach ($this->classes as $className) {
                $this->collections[$className]->save();
            }
        } else {
            $entity->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entityName = null)
    {
         if (null === $entityName) {
             $this->initCollections();
         } else {
             $this->initCollection($entityName);
         }
    }

    /**
     * {@inheritdoc}
     */
    public function getModelClass($name)
    {
        if ( !isset($this->classes[$name]) ) {
            throw new \RuntimeException(sprintf('No class defined for name "%s".', $name));
        }

        return $this->classes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesByLocalesAndDomains(array $locales, array $domains)
    {
        return $this->getFileRepository()->findForLocalesAndDomains($locales, $domains);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileByHash($hash)
    {
        return FileQuery::create()->findOneByHash($hash, $this->getConnection());
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
        return TransUnitQuery::create()->findOneById($id, $this->getConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitByKeyAndDomain($key, $domain)
    {
        $key = mb_substr($key, 0, 255, 'UTF-8');

        $fields = array(
            'Key' => $key,
            'Domain'   => $domain,
        );

        return TransUnitQuery::create()->findOneByArray($fields, $this->getConnection());
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
    public function countTransUnits(array $locales = null, array $filters = null)
    {
        return $this->getTransUnitRepository()->count($locales, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationsFromFile($file, $onlyUpdated)
    {
        return $this->getTransUnitRepository()->getTranslationsForFile($file, $onlyUpdated);
    }

    /**
     * Returns true if translation tables exist.
     *
     * @return boolean
     */
    public function translationsTablesExist()
    {
        return true;
    }

    /**
     * Returns the TransUnit repository.
     *
     * @return TransUnitRepository
     */
    protected function getTransUnitRepository()
    {
        if (null === $this->transUnitRepository) {
            $this->transUnitRepository = new TransUnitRepository($this->getConnection());
        }

        return $this->transUnitRepository;
    }

    /**
     * Returns the File repository.
     *
     * @return FileRepository
     */
    protected function getFileRepository()
    {
        if (null === $this->fileRepository) {
            $this->fileRepository = new FileRepository($this->getConnection());
        }

        return $this->fileRepository;
    }
}
