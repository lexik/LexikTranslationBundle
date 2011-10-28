<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Config\Definition.ConfigurationInterface::getConfigTreeBuilder()
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lexik_translation');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('base_layout')
                    ->cannotBeEmpty()
                    ->defaultValue('LexikTranslationBundle::layout.html.twig')
                 ->end()

                 ->scalarNode('fallback_locale')
                     ->cannotBeEmpty()
                     ->defaultValue('en')
                 ->end()

                ->variableNode('managed_locales')
                    ->cannotBeEmpty()
                    ->defaultValue(array('en'))
                ->end()

                ->scalarNode('force_lower_case')
                    ->defaultFalse()
                ->end()

                ->arrayNode('translator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->cannotBeEmpty()
                            ->defaultValue('Lexik\Bundle\TranslationBundle\Translation\Translator')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('loader')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('database')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')
                                    ->cannotBeEmpty()
                                    ->defaultValue('Lexik\Bundle\TranslationBundle\Translation\Loader\DatabaseLoader')
                                 ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('trans_unit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->cannotBeEmpty()
                            ->defaultValue('Lexik\Bundle\TranslationBundle\Entity\TransUnit')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
