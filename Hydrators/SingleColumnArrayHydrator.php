<?php

namespace Lexik\Bundle\TranslationBundle\Hydrators;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Hydrate result set as "numeric key => value" array.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class SingleColumnArrayHydrator extends AbstractHydrator
{
    /**
     * Hydrates all rows from the current statement instance at once.
     */
    protected function _hydrateAll()
    {
        $result = array();

        while ($data = $this->_stmt->fetch(\PDO::FETCH_NUM)) {
            $value = $data[0];

            if(is_numeric($value)) {
                if (false === mb_strpos($value, '.', 0, 'UTF-8')) {
                    $value = (int) $value;
                } else {
                    $value = (float) $value;
                }
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\ORM\Internal\Hydration.AbstractHydrator::hydrateAllData()
     */
    protected function hydrateAllData()
    {
        return $this->_hydrateAll();
    }
}