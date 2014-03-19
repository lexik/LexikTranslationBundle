<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class LexikTranslationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // set parameters
        sort($config['managed_locales']);
        $container->setParameter('lexik_translation.managed_locales', $config['managed_locales']);
        $container->setParameter('lexik_translation.fallback_locale', $config['fallback_locale']);
        $container->setParameter('lexik_translation.storage', $config['storage']);
        $container->setParameter('lexik_translation.base_layout', $config['base_layout']);
        $container->setParameter('lexik_translation.grid_input_type', $config['grid_input_type']);
        $container->setParameter('lexik_translation.use_yml_tree', $config['use_yml_tree']);

        $this->buildTranslationStorageDefinition($container, $config['storage']['type'], isset($config['storage']['object_manager'])?$config['storage']['object_manager']:null);

        $this->registerTranslatorConfiguration($config, $container);
    }

    /**
     * Build the 'lexik_translation.translation_storage' service definition.
     *
     * @param ContainerBuilder $container
     * @param string           $storage
     */
    protected function buildTranslationStorageDefinition(ContainerBuilder $container, $storage, $objectManager)
    {
        if ('orm' == $storage) {
            if(isset($objectManager)){
                $objectManagerReference = new Reference(sprintf('doctrine.orm.%s_entity_manager', $objectManager));
            } else {
                $objectManagerReference = new Reference('doctrine.orm.entity_manager');
            }
        } else if ('mongodb' == $storage) {
            if(isset($objectManager)){
                $objectManagerReference = new Reference(sprintf('doctrine_mongodb.odm.%s_document_manager', $objectManager));
            } else {
                $objectManagerReference = new Reference('doctrine.odm.mongodb.document_manager');
            }
        } else {
            throw new \RuntimeException(sprintf('Unsupported storage "%s".', $storage));
        }

        $storageDefinition = new Definition();
        $storageDefinition->setClass(new Parameter(sprintf('lexik_translation.%s.translation_storage.class', $storage)));
        $storageDefinition->setArguments(array(
            $objectManagerReference,
            array(
                'trans_unit'  => new Parameter(sprintf('lexik_translation.%s.trans_unit.class', $storage)),
                'translation' => new Parameter(sprintf('lexik_translation.%s.translation.class', $storage)),
                'file'        => new Parameter(sprintf('lexik_translation.%s.file.class', $storage)),
            ),
        ));

        $container->setDefinition('lexik_translation.translation_storage', $storageDefinition);
    }

    /**
     * Register the "lexik_translation.translator" service configuration.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function registerTranslatorConfiguration(array $config, ContainerBuilder $container)
    {
        // use the Lexik translator as default translator service
        $container->setAlias('translator', 'lexik_translation.translator');

        $translator = $container->findDefinition('lexik_translation.translator');
        $translator->addMethodCall('setFallbackLocale', array($config['fallback_locale']));

        $registration = $config['resources_registration'];

        if ('all' == $registration['type'] || 'files' == $registration['type']) {
            // Discover translation directories
            $dirs = array();
            if (class_exists('Symfony\Component\Validator\Validator')) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validator');

                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }
            if (class_exists('Symfony\Component\Form\Form')) {
                $r = new \ReflectionClass('Symfony\Component\Form\Form');

                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }
            $overridePath = $container->getParameter('kernel.root_dir').'/Resources/%s/translations';
            foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
                $reflection = new \ReflectionClass($class);
                if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                    $dirs[] = $dir;
                }
                if (is_dir($dir = sprintf($overridePath, $bundle))) {
                    $dirs[] = $dir;
                }
            }
            if (is_dir($dir = $container->getParameter('kernel.root_dir').'/Resources/translations')) {
                $dirs[] = $dir;
            }

            // Register translation resources
            if (count($dirs) > 0) {
                foreach ($dirs as $dir) {
                    $container->addResource(new DirectoryResource($dir));
                }

                $finder = Finder::create();
                $finder->files();

                if (true === $registration['managed_locales_only']) {
                    // only look for managed locales
                    $finder->name(sprintf('/(.*\.(%s)\..*)/', implode('|', $config['managed_locales'])));
                } else {
                    $finder->filter(function (\SplFileInfo $file) {
                        return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
                    });
                }

                $finder->in($dirs);

                foreach ($finder as $file) {
                    // filename is domain.locale.format
                    list($domain, $locale, $format) = explode('.', $file->getBasename());
                    $translator->addMethodCall('addResource', array($format, (string) $file, $locale, $domain));
                }
            }
        }

        if ('all' == $registration['type'] || 'database' == $registration['type']) {
            // add ressources from database
            $translator->addMethodCall('addDatabaseResources', array());
        }
    }
}
