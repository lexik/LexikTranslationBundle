<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
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
        $container->setParameter('lexik_translation.grid_toggle_similar', $config['grid_toggle_similar']);
        $container->setParameter('lexik_translation.use_yml_tree', $config['use_yml_tree']);
        $container->setParameter('lexik_translation.auto_cache_clean', $config['auto_cache_clean']);

        $objectManager = isset($config['storage']['object_manager']) ? $config['storage']['object_manager'] : null;

        $this->buildTranslationStorageDefinition($container, $config['storage']['type'], $objectManager);

        if (true === $config['auto_cache_clean']) {
            $this->buildCacheCleanListenerDefinition($container);
        }

        $this->registerTranslatorConfiguration($config, $container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function buildCacheCleanListenerDefinition(ContainerBuilder $container)
    {
        $listener = new Definition();
        $listener->setClass('%lexik_translation.listener.clean_translation_cache.class%');

        $listener->addArgument(new Reference('lexik_translation.translation_storage'));
        $listener->addArgument(new Reference('translator'));
        $listener->addArgument(new Parameter('kernel.cache_dir'));
        $listener->addArgument(new Parameter('lexik_translation.managed_locales'));

        $listener->addTag('kernel.event_listener', array(
            'event'  => 'kernel.request',
            'method' => 'onKernelRequest',
        ));

        $container->setDefinition('lexik_translation.listener.clean_translation_cache', $listener);
    }

    /**
     * Build the 'lexik_translation.translation_storage' service definition.
     *
     * @param ContainerBuilder $container
     * @param string           $storage
     * @param string           $objectManager
     * @throws \RuntimeException
     */
    protected function buildTranslationStorageDefinition(ContainerBuilder $container, $storage, $objectManager)
    {
        $container->setParameter('lexik_translation.storage.type', $storage);

        if (StorageInterface::STORAGE_ORM == $storage) {
            $args = array(
                new Reference('doctrine'),
                (null === $objectManager) ? 'default' : $objectManager
            );

            $this->createDoctrineMappingDriver($container, 'lexik_translation.orm.metadata.xml', '%doctrine.orm.metadata.xml.class%');

        } elseif (StorageInterface::STORAGE_MONGODB == $storage) {
            $args = array(
                new Reference('doctrine_mongodb'),
                (null === $objectManager) ? 'default' : $objectManager
            );

            $this->createDoctrineMappingDriver($container, 'lexik_translation.mongodb.metadata.xml', '%doctrine_mongodb.odm.metadata.xml.class%');

        } elseif (StorageInterface::STORAGE_PROPEL == $storage) {
            // In the Propel case the object_manager setting is used for the connection name
            $args = array($objectManager);

        } else {
            throw new \RuntimeException(sprintf('Unsupported storage "%s".', $storage));
        }

        $args[] = array(
            'trans_unit'  => new Parameter(sprintf('lexik_translation.%s.trans_unit.class', $storage)),
            'translation' => new Parameter(sprintf('lexik_translation.%s.translation.class', $storage)),
            'file'        => new Parameter(sprintf('lexik_translation.%s.file.class', $storage)),
        );

        $storageDefinition = new Definition();
        $storageDefinition->setClass(new Parameter(sprintf('lexik_translation.%s.translation_storage.class', $storage)));
        $storageDefinition->setArguments($args);

        $container->setDefinition('lexik_translation.translation_storage', $storageDefinition);
    }

    /**
     * Add a driver to load mapping of model classes.
     *
     * @param ContainerBuilder $container
     * @param string           $driverId
     * @param string           $driverClass
     */
    protected function createDoctrineMappingDriver(ContainerBuilder $container, $driverId, $driverClass)
    {
        $driverDefinition = new Definition($driverClass, array(
            array(realpath(__DIR__.'/../Resources/config/model') => 'Lexik\Bundle\TranslationBundle\Model'),
        ));
        $driverDefinition->setPublic(false);

        $container->setDefinition($driverId, $driverDefinition);
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
        $translator->addMethodCall('setFallbackLocales', array(array($config['fallback_locale'])));

        $registration = $config['resources_registration'];

        // Discover translation directories
        if ('all' == $registration['type'] || 'files' == $registration['type']) {
            $dirs = array();

            if (class_exists('Symfony\Component\Validator\Validation')) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validation');

                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }

            if (class_exists('Symfony\Component\Form\Form')) {
                $r = new \ReflectionClass('Symfony\Component\Form\Form');

                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }

            if (class_exists('Symfony\Component\Security\Core\Exception\AuthenticationException')) {
                $r = new \ReflectionClass('Symfony\Component\Security\Core\Exception\AuthenticationException');

                if (is_dir($dir = dirname($r->getFilename()).'/../Resources/translations')) {
                    $dirs[] = $dir;
                }
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
                    list($domain, $locale, $format) = explode('.', $file->getBasename(), 3);
                    $translator->addMethodCall('addResource', array($format, (string) $file, $locale, $domain));
                }
            }
        }

        // add resources from database
        if ('all' == $registration['type'] || 'database' == $registration['type']) {
            $translator->addMethodCall('addDatabaseResources', array());
        }
    }
}
