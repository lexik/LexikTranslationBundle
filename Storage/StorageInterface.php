<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;
use DateTime;

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
    public const STORAGE_ORM     = 'orm';
    public const STORAGE_MONGODB = 'mongodb';

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
     * @return TransUnitInterface
     */
    public function getTransUnitById($id): TransUnitInterface;

    /**
     * Returns a TransUnit by its key and domain.
     *
     * @param string $key
     * @param string $domain
     * @return TransUnitInterface
     */
    public function getTransUnitByKeyAndDomain($key, $domain): TransUnitInterface;

    /**
     * Returns all trans unit with translations for the given domain and locale.
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getTransUnitsByLocaleAndDomain($locale, $domain): array;

    /**
     * Returns some trans units with their translations.
     *
     * @param int   $rows
     * @param int   $page
     * @return array
     */
    public function getTransUnitList(?array $locales = null, $rows = 20, $page = 1, ?array $filters = null): array;

    /**
     * Count the number of trans unit.
     *
     * @return int
     */
    public function countTransUnits(?array $locales = null,  ?array $filters = null): int;

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
    public function getLatestUpdatedAt(): ?DateTime;

    /**
     * Returns the number or trans unit for each domain.
     *
     * @return array
     */
    public function getCountTransUnitByDomains(): array;

    /**
     * Returns the number or translations for each locales for the given domain.
     *
     * @param string $domain
     * @return array
     */
    public function getCountTranslationByLocales($domain): array;
}
