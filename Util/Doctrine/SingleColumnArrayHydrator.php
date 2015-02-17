<?php

namespace Lexik\Bundle\TranslationBundle\Util\Doctrine;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Hydrate result set as "numeric key => value" array.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class SingleColumnArrayHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
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
}
