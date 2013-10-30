<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileRepository extends EntityRepository
{
    /**
     * Returns all files matching a given locale and a given domains.
     *
     * @param array $locales
     * @param array $domains
     * @return array
     */
    public function findForLocalesAndDomains(array $locales, array $domains)
    {
        $builder = $this->createQueryBuilder('f');

        if (count($locales) > 0) {
            $builder->andWhere($builder->expr()->in('f.locale', $locales));
        }

        if (count($domains) > 0) {
            $builder->andWhere($builder->expr()->in('f.domain', $domains));
        }

        return $builder->getQuery()->getResult();
    }
}
