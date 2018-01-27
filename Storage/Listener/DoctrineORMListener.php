<?php

namespace Lexik\Bundle\TranslationBundle\Storage\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineORMListener
{
    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $params = $eventArgs->getEntityManager()->getConnection()->getParams();

        if (!isset($params['charset']) || 'utf8mb4' !== strtolower($params['charset'])) {
            return;
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (false === strpos($metadata->getName(), 'TranslationBundle')) {
            return;
        }

        foreach ($metadata->getFieldNames() as $name) {
            $fieldMapping = $metadata->getFieldMapping($name);

            if (isset($fieldMapping['type']) && 'string' === $fieldMapping['type']) {
                $fieldMapping['length'] = 191;
                $metadata->fieldMappings[$name] = $fieldMapping;
            }
        }
    }
}
