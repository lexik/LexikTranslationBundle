<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * Translation storage interface.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface StorageInterface
{
    /**
     * All the available config storage types.
     */
    const STORAGE_ORM     = 'orm';
    const STORAGE_MONGODB = 'mongodb';
    const STORAGE_PROPEL  = 'propel';

    /**
     * Persist the given object.
     *
     * @param object $entity
     */
    public function persist($entity);

    /**
     * Delete the given object.
     *
     * @param object $entity
     */
    public function remove($entity);

    /**
     * Flush changes.
     *
     * @param string $entity
     */
    public function flush($entity = null);

    /**
     * Clear managed objects.
     *
     * @param string $entityName
     */
    public function clear($entityName = null);

    /**
     * Returns the class's namespace according to the given name.
     *
     * @param string $name
     */
    public function getModelClass($name);

    /**
     * Returns all files matching a given locale and a given domains.
     *
     * @param array $locales
     * @param array $domains
     * @return array
     */
    public function getFilesByLocalesAndDomains(array $locales, array $domains);

    /**
     * Returns a File by its hash.
     *
     * @param string $hash
     */
    public function getFileByHash($hash);

    /**
     * Returns all domains available in database.
     *
     * @return array
     */
    public function getTransUnitDomains();

    /**
     * Returns all domains for each locale.
     *
     * @return array
     */
    public function getTransUnitDomainsByLocale();

    /**
     * Returns a TransUnit by its id.
     *
     * @param int $id
     * @return TransUnit
     */
    public function getTransUnitById($id);

    /**
     * Returns a TransUnit by its key and domain.
     *
     * @param string $key
     * @param string $domain
     * @return TransUnitInterface
     */
    public function getTransUnitByKeyAndDomain($key, $domain);

    /**
     * Returns all trans unit with translations for the given domain and locale.
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getTransUnitsByLocaleAndDomain($locale, $domain);

    /**
     * Returns some trans units with their translations.
     *
     * @param array $locales
     * @param int   $rows
     * @param int   $page
     * @param array $filters
     * @return array
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null);

    /**
     * Count the number of trans unit.
     *
     * @param array $locales
     * @param array $filters
     * @return int
     */
    public function countTransUnits(array $locales = null,  array $filters = null);

    /**
     * Returns all translations for the given file.
     *
     * @param FileInterface $file
     * @param boolean       $onlyUpdated
     * @return array
     */
    public function getTranslationsFromFile($file, $onlyUpdated);

    /**
     * Returns the latest updatedAt date among all translation.
     *
     * @return \DateTime|null
     */
    public function getLatestUpdatedAt();
}
