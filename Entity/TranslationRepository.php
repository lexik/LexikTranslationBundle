<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Datetime;

/**
 * Repository for Translation entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationRepository extends EntityRepository
{
    /**
     * @return ?DateTime
     */
    public function getLatestTranslationUpdatedAt(): ?DateTime
    {
        $date = $this->createQueryBuilder('t')
            ->select('MAX(t.updatedAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return !empty($date) ? new DateTime($date) : null;
    }

    /**
     * @param string $domain
     * @return array
     */
    public function countByLocales($domain): mixed
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.id) AS number, t.locale')
            ->innerJoin('t.transUnit', 'tu')
            ->andWhere('tu.domain = :domain')
            ->setParameter('domain', $domain)
            ->groupBy('t.locale')
            ->getQuery()
            ->getResult();
    }
}
