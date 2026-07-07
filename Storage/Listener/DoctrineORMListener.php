<?php

namespace Lexik\Bundle\TranslationBundle\Storage\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

class DoctrineORMListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $params = $eventArgs->getEntityManager()->getConnection()->getParams();

        if (!isset($params['charset']) || 'utf8mb4' !== strtolower($params['charset'])) {
            return;
        }

        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (!str_contains($metadata->getName(), 'TranslationBundle')) {
            return;
        }

        foreach ($metadata->getFieldNames() as $name) {
            $fieldMapping = $metadata->getFieldMapping($name);

            if (\is_array($fieldMapping)) {
                // Doctrine ORM 2.x / legacy array mapping
                if (isset($fieldMapping['type']) && 'string' === $fieldMapping['type']) {
                    $fieldMapping['length'] = 191;
                    $metadata->fieldMappings[$name] = $fieldMapping;
                }

                continue;
            }

            // Doctrine ORM 3+: FieldMapping object — avoid deprecated ArrayAccess (removed in ORM 4.0)
            if ('string' === $fieldMapping->type) {
                $fieldMapping->length = 191;
                $metadata->fieldMappings[$name] = $fieldMapping;
            }
        }
    }
}
