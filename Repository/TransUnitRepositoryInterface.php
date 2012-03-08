<?php

namespace Lexik\Bundle\TranslationBundle\Repository;

use Lexik\Bundle\TranslationBundle\Model\File;

/**
 * Defines all method document and entity repositories have to implement.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface TransUnitRepositoryInterface
{
    /**
     * Returns all domain available in database.
     *
     * @return array
     */
    public function getAllDomains();

    /**
     * Returns all domains for each locale.
     *
     * @return array
     */
    public function getAllDomainsByLocale();

    /**
     * Returns all trans unit with translations for the given domain and locale.
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getAllByLocaleAndDomain($locale, $domain);

    /**
     * Returns some trans units with their translations.
     *
     * @param array $locales
     * @param int $rows
     * @param int $page
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
    public function count(array $locales = null,  array $filters = null);

    /**
     * Returns all translations for the given file.
     *
     * @param File $file
     * @param boolean $onlyUpdated
     * @return array
     */
    public function getTranslationsForFile(File $file, $onlyUpdated);
}