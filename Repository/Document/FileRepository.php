<?php

namespace Lexik\Bundle\TranslationBundle\Repository\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;

use Lexik\Bundle\TranslationBundle\Repository\FileRepositoryInterface;

/**
 * Repository for TransUnit document.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepository extends DocumentRepository implements FileRepositoryInterface
{
    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Repository.FileRepositoryInterface::findForLoalesAndDomains()
     */
    public function findForLoalesAndDomains(array $locales, array $domains)
    {
        $builder = $this->createQueryBuilder();

        if (count($locales) > 0) {
            $builder->field('locale')->in($locales);
        }

        if (count($domains) > 0) {
            $builder->field('domain')->in($domains);
        }

        $cursor = $builder->getQuery()->execute();

        $files = array();
        foreach ($cursor as $result) {
            $files[] = $result;
        }

        return $files;
    }
}