<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Doctrine\ORM\Events;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class LexikTranslationExtension extends Extension implements PrependExtensionInterface
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

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // set parameters
        sort($config['managed_locales']);
        $container->setParameter('lexik_translation.managed_locales', $config['managed_locales']);
        $container->setParameter('lexik_translation.fallback_locale', $config['fallback_locale']);
        $container->setParameter('lexik_translation.storage', $config['storage']);
        $container->setParameter('lexik_translation.base_layout', $config['base_layout']);
        $container->setParameter('lexik_translation.grid_input_type', $config['grid_input_type']);
        $container->setParameter('lexik_translation.grid_toggle_similar', $config['grid_toggle_similar']);
        $container->setParameter('lexik_translation.auto_cache_clean', $config['auto_cache_clean']);
        $container->setParameter('lexik_translation.dev_tools.enable', $config['dev_tools']['enable']);
        $container->setParameter('lexik_translation.dev_tools.create_missing', $config['dev_tools']['create_missing']);
        $container->setParameter('lexik_translation.dev_tools.file_format', $config['dev_tools']['file_format']);
        $container->setParameter('lexik_translation.exporter.json.hierarchical_format', $config['exporter']['json_hierarchical_format']);
        $container->setParameter('lexik_translation.exporter.yml.use_tree', $config['exporter']['use_yml_tree']);

        $objectManager = isset($config['storage']['object_manager']) ? $config['storage']['object_manager'] : null;

        $this->buildTranslatorDefinition($container);
        $this->buildTranslationStorageDefinition($container, $config['storage']['type'], $objectManager);

        if (true === $config['auto_cache_clean']) {
            $this->buildCacheCleanListenerDefinition($container, $config['auto_cache_clean_interval']);
        }

        if (true === $config['dev_tools']['enable']) {
            $this->buildDevServicesDefinition($container);
        }

        $this->registerTranslatorConfiguration($config, $container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function buildTranslatorDefinition(ContainerBuilder $container)
    {
        $translator = new Definition();
        $translator->setClass('%lexik_translation.translator.class%');

        if (Kernel::VERSION_ID >= 30400) {
            $arguments = [
                new Reference('service_container'), // Will be replaced by service locator
                new Reference('translator.formatter.default'),
                new Parameter('kernel.default_locale'),
                [], // translation loaders
                new Parameter('lexik_translation.translator.options')
            ];
            $translator->setPublic(true);
        } elseif (Kernel::VERSION_ID >= 30300) {
            $arguments = [
                new Reference('service_container'), // Will be replaced by service locator
                new Reference('translator.selector'),
                new Parameter('kernel.default_locale'),
                [], // translation loaders
                new Parameter('lexik_translation.translator.options')
            ];
        } else {
            $arguments = [
                new Reference('service_container'),
                new Reference('translator.selector'),
                [], // translation loaders
                new Parameter('lexik_translation.translator.options')
            ];
        }

        $translator->setArguments($arguments);
        $translator->addMethodCall('setConfigCacheFactory', [new Reference('config_cache_factory')]);
        $translator->addTag('kernel.locale_aware');

        $container->setDefinition('lexik_translation.translator', $translator);
    }

    /**
     * @param ContainerBuilder $container
     * @param int $cacheInterval
     */
    public function buildCacheCleanListenerDefinition(ContainerBuilder $container, $cacheInterval)
    {
        $listener = new Definition();
        $listener->setClass('%lexik_translation.listener.clean_translation_cache.class%');

        $listener->addArgument(new Reference('lexik_translation.translation_storage'));
        $listener->addArgument(new Reference('translator'));
        $listener->addArgument(new Parameter('kernel.cache_dir'));
        $listener->addArgument(new Reference('lexik_translation.locale.manager'));
        $listener->addArgument($cacheInterval);

        $listener->addTag('kernel.event_listener', array(
            'event'  => 'kernel.request',
            'method' => 'onKernelRequest',
        ));

        $container->setDefinition('lexik_translation.listener.clean_translation_cache', $listener);
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $rootDir = 'vendor/lexik/translation-bundle/Resources/views';

        // Only symfony versions >= 3.3 include the kernel.project_dir parameter
        if (Kernel::VERSION_ID >= 30300) {
            $rootDir = '%kernel.project_dir%/'.$rootDir;
        } else {
            $rootDir = '%kernel.project_dir%/../'.$rootDir;
        }

        $container->prependExtensionConfig('twig', [
            'paths' => [
                $rootDir => 'LexikTranslationBundle'
            ]
        ]);
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
                (null === $objectManager) ? 'default' : $objectManager,
            );

            $this->createDoctrineMappingDriver($container, 'lexik_translation.orm.metadata.xml', '%doctrine.orm.metadata.xml.class%');

            $metadataListener = new Definition();
            $metadataListener->setClass('%lexik_translation.orm.listener.class%');
            $metadataListener->addTag('doctrine.event_listener', array(
                'event' => Events::loadClassMetadata,
            ));

            $container->setDefinition('lexik_translation.orm.listener', $metadataListener);

        } elseif (StorageInterface::STORAGE_MONGODB == $storage) {
            $args = array(
                new Reference('doctrine_mongodb'),
                (null === $objectManager) ? 'default' : $objectManager,
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
        $storageDefinition->setClass($container->getParameter(sprintf('lexik_translation.%s.translation_storage.class', $storage)));
        $storageDefinition->setArguments($args);
        $storageDefinition->setPublic(true);

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
     * Load dev tools.
     *
     * @param ContainerBuilder $container
     */
    protected function buildDevServicesDefinition(ContainerBuilder $container)
    {
        $container
            ->getDefinition('lexik_translation.data_grid.request_handler')
            ->addMethodCall('setProfiler', array(new Reference('profiler')));

        $tokenFinderDefinition = new Definition();
        $tokenFinderDefinition->setClass($container->getParameter('lexik_translation.token_finder.class'));
        $tokenFinderDefinition->setArguments(array(
            new Reference('profiler'),
            new Parameter('lexik_translation.token_finder.limit'),
        ));

        $container->setDefinition('lexik_translation.token_finder', $tokenFinderDefinition);
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
        $alias = $container->setAlias('translator', 'lexik_translation.translator');

        if (Kernel::VERSION_ID >= 30400) {
            $alias->setPublic(true);
        }

        $translator = $container->findDefinition('lexik_translation.translator');
        $translator->addMethodCall('setFallbackLocales', array($config['fallback_locale']));

        $registration = $config['resources_registration'];

        // Discover translation directories
        if ('all' === $registration['type'] || 'files' === $registration['type']) {
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

            $overridePath = $container->getParameter('kernel.project_dir').'/Resources/%s/translations';

            foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
                $reflection = new \ReflectionClass($class);

                if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                    $dirs[] = $dir;
                }

                if (is_dir($dir = sprintf($overridePath, $bundle))) {
                    $dirs[] = $dir;
                }
            }

            if (is_dir($dir = $container->getParameter('kernel.project_dir').'/Resources/translations')) {
                $dirs[] = $dir;
            }

            if (Kernel::MAJOR_VERSION >= 4 && is_dir($dir = $container->getParameter('kernel.project_dir').'/translations')) {
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
                    $finder->name(sprintf('/(.*\.(%s)\.\w+$)/', implode('|', $config['managed_locales'])));
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
        if ('all' === $registration['type'] || 'database' === $registration['type']) {
            $translator->addMethodCall('addDatabaseResources', array());
        }
    }
}
