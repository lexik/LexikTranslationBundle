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
        $loadersIds = array();

        foreach ($container->findTaggedServiceIds('translation.loader') as $id => $attributes) {
            $loaders[$id] = new Reference($id);
            $loadersIds[$id] = $attributes[0]['alias'];
        }

        if ($container->hasDefinition('lexik_translation.translator')) {
            $container->findDefinition('lexik_translation.translator')->replaceArgument(2, $loadersIds);
        }

        if ($container->hasDefinition('lexik_translation.importer.file_importer')) {
            $container->findDefinition('lexik_translation.importer.file_importer')->replaceArgument(0, $loaders);
        }
    }
}