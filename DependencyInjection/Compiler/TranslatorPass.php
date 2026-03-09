<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\ExporterCollector;
use Lexik\Bundle\TranslationBundle\Translation\Importer\FileImporter;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

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
    public function process(ContainerBuilder $container): void
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

        // Find the translator service by alias or class name
        $translatorId = null;
        if ($container->hasDefinition('lexik_translation.translator')) {
            $translatorId = 'lexik_translation.translator';
        } elseif ($container->hasAlias('lexik_translation.translator')) {
            $translatorId = (string) $container->getAlias('lexik_translation.translator');
        } elseif ($container->hasDefinition('Lexik\Bundle\TranslationBundle\Translation\Translator')) {
            $translatorId = 'Lexik\Bundle\TranslationBundle\Translation\Translator';
        }

        if ($translatorId && $container->hasDefinition($translatorId)) {
            $translatorDef = $container->findDefinition($translatorId);

            $serviceRefs = [...$loadersReferencesById, ...['event_dispatcher' => new Reference('event_dispatcher')]];

            // Use named arguments if available, otherwise use numeric indices
            if ($translatorDef->getArguments() && array_key_exists('$container', $translatorDef->getArguments())) {
                $translatorDef->replaceArgument('$container', ServiceLocatorTagPass::register($container, $serviceRefs));
                $translatorDef->replaceArgument('$loaderIds', $loaders);
            } else {
                $translatorDef->replaceArgument(0, ServiceLocatorTagPass::register($container, $serviceRefs));
                $translatorDef->replaceArgument(3, $loaders);
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
