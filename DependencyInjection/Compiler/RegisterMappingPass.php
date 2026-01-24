<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Doctrine metadata pass to add a driver to load model class mapping.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class RegisterMappingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $storage = $container->getParameter('lexik_translation.storage');

        $name = empty($storage['object_manager']) ? 'default' : $storage['object_manager'];

        $ormDriverId     = sprintf('doctrine.orm.%s_metadata_driver', $name);
        $mongodbDriverId = sprintf('doctrine_mongodb.odm.%s_metadata_driver', $name);

        if (StorageInterface::STORAGE_ORM == $storage['type'] && $container->hasDefinition($ormDriverId)) {
            // Models now use PHP attributes, so we need to use AttributeDriver
            // Create attribute driver if it doesn't exist (fallback if Extension didn't create it)
            $attributeDriverId = 'lexik_translation.orm.metadata.attribute';
            
            // Ensure attribute driver exists - create it if Extension didn't create it
            if (!$container->hasDefinition($attributeDriverId)) {
                // Calculate bundle path using ReflectionClass
                $bundleReflection = new \ReflectionClass(\Lexik\Bundle\TranslationBundle\LexikTranslationBundle::class);
                $bundleDir = dirname($bundleReflection->getFileName());
                $modelPath = $bundleDir . '/Model';
                
                // Try to get realpath
                $realModelPath = realpath($modelPath);
                if ($realModelPath) {
                    $modelPath = $realModelPath;
                }
                
                // Create AttributeDriver service
                $driverDefinition = new Definition(AttributeDriver::class, [
                    [$modelPath]
                ]);
                $driverDefinition->setPublic(false);
                $container->setDefinition($attributeDriverId, $driverDefinition);
            }
            
            // Register attribute driver for models namespace
            // IMPORTANT: We need to check if it's already registered to avoid duplicates
            $ormDriver = $container->getDefinition($ormDriverId);
            $methodCalls = $ormDriver->getMethodCalls();
            
            // Check if attribute driver is already registered
            $attributeDriverRegistered = false;
            foreach ($methodCalls as $call) {
                if ($call[0] === 'addDriver' && 
                    isset($call[1][0]) && 
                    $call[1][0] instanceof Reference &&
                    (string)$call[1][0] === $attributeDriverId) {
                    $attributeDriverRegistered = true;
                    break;
                }
            }
            
            // Register XML driver for Entity namespace FIRST (entities use XML mapping)
            // This must be registered before Model namespace to ensure entities are recognized
            // Create XML driver for entities if it doesn't exist
            $entityXmlDriverId = 'lexik_translation.orm.metadata.entity.xml';
            if (!$container->hasDefinition($entityXmlDriverId)) {
                // Use the same XML driver class but with different path for entities
                $bundleReflection = new \ReflectionClass(\Lexik\Bundle\TranslationBundle\LexikTranslationBundle::class);
                $bundleDir = dirname($bundleReflection->getFileName());
                $doctrinePath = $bundleDir . '/Resources/config/doctrine';
                
                $realDoctrinePath = realpath($doctrinePath);
                if ($realDoctrinePath) {
                    $doctrinePath = $realDoctrinePath;
                }
                
                // Create XML driver for entities using the same class as the model XML driver
                $xmlDriverClass = $container->getParameter('doctrine.orm.metadata.xml.class');
                $entityDriverDefinition = new Definition($xmlDriverClass, [
                    [$doctrinePath => 'Lexik\Bundle\TranslationBundle\Entity'],
                    SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION,
                    true
                ]);
                $entityDriverDefinition->setPublic(false);
                $container->setDefinition($entityXmlDriverId, $entityDriverDefinition);
            }
            
            // Register XML driver for Entity namespace FIRST
            $entityDriverRegistered = false;
            foreach ($methodCalls as $call) {
                if ($call[0] === 'addDriver' && 
                    isset($call[1][1]) && 
                    $call[1][1] === 'Lexik\Bundle\TranslationBundle\Entity') {
                    $entityDriverRegistered = true;
                    break;
                }
            }
            
            if (!$entityDriverRegistered && $container->hasDefinition($entityXmlDriverId)) {
                // Insert at the beginning to ensure Entity namespace is processed first
                $newMethodCalls = [[
                    'addDriver',
                    [new Reference($entityXmlDriverId), 'Lexik\Bundle\TranslationBundle\Entity']
                ]];
                foreach ($methodCalls as $call) {
                    $newMethodCalls[] = $call;
                }
                $ormDriver->setMethodCalls($newMethodCalls);
                $methodCalls = $newMethodCalls; // Update for next checks
            }
            
            // Register attribute driver if not already registered
            if (!$attributeDriverRegistered && $container->hasDefinition($attributeDriverId)) {
                // Remove any existing registration for the Model namespace (XML driver)
                $newMethodCalls = [];
                foreach ($methodCalls as $call) {
                    // Skip XML driver registration for Model namespace
                    if ($call[0] === 'addDriver' && 
                        isset($call[1][1]) && 
                        $call[1][1] === 'Lexik\Bundle\TranslationBundle\Model' &&
                        isset($call[1][0]) &&
                        $call[1][0] instanceof Reference &&
                        (string)$call[1][0] === 'lexik_translation.orm.metadata.xml') {
                        // Skip this call - we'll replace it with AttributeDriver
                        continue;
                    }
                    $newMethodCalls[] = $call;
                }
                
                // Add attribute driver for Model namespace
                $newMethodCalls[] = [
                    'addDriver',
                    [new Reference($attributeDriverId), 'Lexik\Bundle\TranslationBundle\Model']
                ];
                
                // Update method calls
                $ormDriver->setMethodCalls($newMethodCalls);
            } elseif (!$container->hasDefinition($attributeDriverId) && $container->hasDefinition('lexik_translation.orm.metadata.xml')) {
                // Fallback to XML driver only if attribute driver doesn't exist
                $ormDriver->addMethodCall(
                    'addDriver',
                    [new Reference('lexik_translation.orm.metadata.xml'), 'Lexik\Bundle\TranslationBundle\Model']
                );
            }
        }

        if (StorageInterface::STORAGE_MONGODB == $storage['type'] && $container->hasDefinition($mongodbDriverId)) {
            $container->getDefinition($mongodbDriverId)->addMethodCall(
                'addDriver',
                [new Reference('lexik_translation.mongodb.metadata.xml'), 'Lexik\Bundle\TranslationBundle\Model']
            );
        }
    }
}
