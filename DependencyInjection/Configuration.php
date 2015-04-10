<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
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

        $storages = array(
            StorageInterface::STORAGE_ORM,
            StorageInterface::STORAGE_MONGODB,
            StorageInterface::STORAGE_PROPEL,
        );
        $registrationTypes = array('all', 'files', 'database');
        $inputTypes = array('text', 'textarea');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('base_layout')
                    ->cannotBeEmpty()
                    ->defaultValue('LexikTranslationBundle::layout.html.twig')
                ->end()

                ->scalarNode('fallback_locale')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()

                ->arrayNode('managed_locales')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->prototype('scalar')->end()
                ->end()

                ->scalarNode('grid_input_type')
                    ->cannotBeEmpty()
                    ->defaultValue('text')
                    ->validate()
                        ->ifNotInArray($inputTypes)
                        ->thenInvalid('The input type "%s" is not supported. Please use one of the following types: '.implode(', ', $inputTypes))
                    ->end()
                ->end()

                ->booleanNode('grid_toggle_similar')
                    ->defaultValue(false)
                ->end()

                ->booleanNode('auto_cache_clean')
                    ->defaultValue(false)
                ->end()

                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->cannotBeEmpty()
                            ->defaultValue(StorageInterface::STORAGE_ORM)
                            ->validate()
                                ->ifNotInArray($storages)
                                ->thenInvalid('The storage "%s" is not supported. Please use one of the following storage: '.implode(', ', $storages))
                            ->end()
                        ->end()
                        ->scalarNode('object_manager')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('resources_registration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->cannotBeEmpty()
                            ->defaultValue('all')
                            ->validate()
                                ->ifNotInArray($registrationTypes)
                                ->thenInvalid('Invalid registration type "%s". Please use one of the following types: '.implode(', ', $registrationTypes))
                            ->end()
                        ->end()
                        ->booleanNode('managed_locales_only')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()

                ->booleanNode('use_yml_tree')
                    ->defaultValue(false)
                ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}
