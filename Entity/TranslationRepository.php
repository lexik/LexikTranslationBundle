<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for Translation entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationRepository extends EntityRepository
{
    /**
     * @return \DateTime|null
     */
    public function getLatestTranslationUpdatedAt()
    {
        $date = $this->createQueryBuilder('t')
            ->select('MAX(t.updatedAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return !empty($date) ? new \DateTime($date) : null;
    }
}
