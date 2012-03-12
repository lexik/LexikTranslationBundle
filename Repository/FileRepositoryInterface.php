<?php

namespace Lexik\Bundle\TranslationBundle\Repository;

use Lexik\Bundle\TranslationBundle\Model\File;

/**
 * Defines all method document and entity repositories have to implement.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface FileRepositoryInterface
{

    /**
     * Returns all files mathing a given locale and a given domains.
     *
     * @param array $locales
     * @param array $domains
     * @return array
     */
    public function findForLoalesAndDomains(array $locales, array $domains);
}