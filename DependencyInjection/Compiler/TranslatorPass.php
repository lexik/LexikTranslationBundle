<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\ExporterCollector;
use Lexik\Bundle\TranslationBundle\Translation\Importer\FileImporter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        // Keep the compatibility with old versions of the bundle (options fallback_locale and managed_locales).
        $this->processEnabledLocales($container);
        $this->processFallbacksLocales($container);

        $this->processRessources($container);

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
            $translatorDef = $container->findDefinition('lexik_translation.translator');

            $serviceRefs = [...$loadersReferencesById, ...['event_dispatcher' => new Reference('event_dispatcher')]];

            $translatorDef->replaceArgument('$container', ServiceLocatorTagPass::register($container, $serviceRefs));
            $translatorDef->replaceArgument('$loaderIds', $loaders);
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

    private function processRessources(ContainerBuilder $container): void
    {
        $translator = $container->getDefinition('translator.default');

        $defaultOptions = $translator->getArgument(4);

        // If the resources type is set to "database", we don't want to load any resource file, as they will be loaded from the database.
        $ressourcesType = $container->getParameter('lexik_translation.resources_type');

        if ('database' === $ressourcesType) {
            $defaultOptions['resource_files'] = [];
            $translator->replaceArgument(4, $defaultOptions);
            return;
        }

        // If the option "managed_locales_only" is set to true, we only want to load the resource files for the enabled locales.
        if (true === $container->getParameter('lexik_translation.managed_locales_only')) {
            $enabledLocales = $translator->getArgument(5);

            $defaultOptions['resource_files'] = array_filter($defaultOptions['resource_files'], static function ($locale) use ($enabledLocales) {
                return in_array($locale, $enabledLocales, true);
            }, \ARRAY_FILTER_USE_KEY);

            $translator->replaceArgument(4, $defaultOptions);
        }
    }

    private function processFallbacksLocales(ContainerBuilder $container): void
    {
        $fallbackLocale = $container->getParameter('lexik_translation.fallback_locale');

        if (!empty($fallbackLocale)) {
            $translator = $container->findDefinition('translator.default');
            $translator->addMethodCall('setFallbackLocales', [ $fallbackLocale ]);
        }
    }

    private function processEnabledLocales(ContainerBuilder $container): void
    {
        $enabledLocales = $container->getParameter('kernel.enabled_locales');

        if (empty($enabledLocales)) {
            $fallbackEnabledLocales = $container->getParameter('lexik_translation.managed_locales');
            if (!empty($fallbackEnabledLocales)) {
                $enabledLocales = $fallbackEnabledLocales;
            }
            $translator = $container->findDefinition('translator.default');
            $translator->replaceArgument(5, $enabledLocales);
        }
    }
}
