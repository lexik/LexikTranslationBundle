<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Translator compiler pass to automatically pass loader to the translator service.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('lexik_translation.translator')) {
            $loaders = array();
            foreach ($container->findTaggedServiceIds('translation.loader') as $id => $attributes) {
                $loaders[$id] = $attributes[0]['alias'];
            }

            $container->findDefinition('lexik_translation.translator')->replaceArgument(2, $loaders);
        }
    }
}