<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\ExporterCollector;
use Lexik\Bundle\TranslationBundle\Translation\Importer\FileImporter;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Translator compiler pass to automatically pass loader to the other services.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // loaders
        $loaders = [];
        $loadersReferences = [];
        $loadersReferencesById = [];

        foreach ($container->findTaggedServiceIds('translation.loader', true) as $id => $attributes) {
            $loaders[$id][] = $attributes[0]['alias'];
            $loadersReferencesById[$id] = new Reference($id);
            $loadersReferences[$attributes[0]['alias']] = new Reference($id);

            if (isset($attributes[0]['legacy-alias'])) {
                $loaders[$id][] = $attributes[0]['legacy-alias'];
                $loadersReferences[$attributes[0]['legacy-alias']] = new Reference($id);
            }
        }

        if ($container->hasDefinition('lexik_translation.translator')) {
            if (Kernel::VERSION_ID >= 30300) {
                $serviceRefs = [...$loadersReferencesById, ...['event_dispatcher' => new Reference('event_dispatcher')]];

                $container->findDefinition('lexik_translation.translator')
                    ->replaceArgument(0, ServiceLocatorTagPass::register($container, $serviceRefs))
                    ->replaceArgument(3, $loaders);
            } else {
                $container->findDefinition('lexik_translation.translator')->replaceArgument(2, $loaders);
            }
        }

        if ($container->hasDefinition(FileImporter::class)) {
            $container->findDefinition(FileImporter::class)->replaceArgument(0, $loadersReferences);
        }

        // exporters
        if ($container->hasDefinition(ExporterCollector::class)) {
            foreach ($container->findTaggedServiceIds('lexik_translation.exporter') as $id => $attributes) {
                $container->getDefinition(ExporterCollector::class)->addMethodCall('addExporter', [$id, new Reference($id)]);
            }
        }
    }
}
