<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Repository for TransUnit document.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepository extends DocumentRepository
{
    /**
     * Returns all available domain/locale couples.
     *
     * @return array
     */
    public function findForLocalesAndDomains(array $locales, array $domains): array
    {
        $builder = $this->createQueryBuilder();

        if (count($locales) > 0) {
            $builder->field('locale')->in($locales);
        }

        if (count($domains) > 0) {
            $builder->field('domain')->in($domains);
        }

        $cursor = $builder->getQuery()->execute();

        $files = [];
        foreach ($cursor as $result) {
            $files[] = $result;
        }

        return $files;
    }
}
