<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler;

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
    public function process(ContainerBuilder $container)
    {
        $loaders = array();
        $loadersReferences = array();

        foreach ($container->findTaggedServiceIds('translation.loader') as $id => $attributes) {
            $loadersReferences[$id] = new Reference($id);

            $loaders[$id][] = $attributes[0]['alias'];
            if (isset($attributes[0]['legacy-alias'])) {
                $loaders[$id][] = $attributes[0]['legacy-alias'];
            }
        }

        if ($container->hasDefinition('lexik_translation.translator')) {
            $container->findDefinition('lexik_translation.translator')->replaceArgument(2, $loaders);
        }

        if ($container->hasDefinition('lexik_translation.importer.file')) {
            $container->findDefinition('lexik_translation.importer.file')->replaceArgument(0, $loadersReferences);
        }
    }
}