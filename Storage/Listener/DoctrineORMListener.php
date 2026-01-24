<?php

namespace Lexik\Bundle\TranslationBundle\Storage\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineORMListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $params = $eventArgs->getEntityManager()->getConnection()->getParams();

        if (!isset($params['charset']) || 'utf8mb4' !== strtolower($params['charset'])) {
            return;
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (!str_contains((string) $metadata->getName(), 'TranslationBundle')) {
            return;
        }

        foreach ($metadata->getFieldNames() as $name) {
            $fieldMapping = $metadata->getFieldMapping($name);

            // Use properties instead of ArrayAccess for Doctrine ORM 3.0+ compatibility
            // FieldMapping is now an object with properties, not an array
            // Check if it's an array (legacy) or object (Doctrine ORM 3.0+)
            if (is_array($fieldMapping)) {
                // Legacy array format (for backward compatibility with Doctrine ORM 2.x)
                if (isset($fieldMapping['type']) && 'string' === $fieldMapping['type']) {
                    $fieldMapping['length'] = 191;
                    $metadata->fieldMappings[$name] = $fieldMapping;
                }
            } else {
                // Object format (Doctrine ORM 3.0+) - use properties directly
                if (isset($fieldMapping->type) && 'string' === $fieldMapping->type) {
                    $fieldMapping->length = 191;
                    $metadata->fieldMappings[$name] = $fieldMapping;
                }
            }
        }
    }
}
