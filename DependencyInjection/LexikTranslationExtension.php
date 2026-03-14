<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Lexik\Bundle\TranslationBundle\LexikTranslationBundle;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Resolve managed_locales: fallback to framework.enabled_locales if not set
        $managedLocales = $config['managed_locales'];
        if (empty($managedLocales)) {
            $managedLocales = $container->hasParameter('kernel.enabled_locales')
                ? $container->getParameter('kernel.enabled_locales')
                : [];
        }
        sort($managedLocales);

        $container->setParameter('lexik_translation.managed_locales', $managedLocales);
        $container->setParameter('lexik_translation.fallback_locale', $config['fallback_locale']);
        $container->setParameter('lexik_translation.storage', $config['storage']);
        $container->setParameter('lexik_translation.resources_type', $config['resources_registration']['type']);
        $container->setParameter('lexik_translation.managed_locales_only', $config['resources_registration']['managed_locales_only']);
        $container->setParameter('lexik_translation.base_layout', $config['base_layout']);
        $container->setParameter('lexik_translation.grid_input_type', $config['grid_input_type']);
        $container->setParameter('lexik_translation.grid_toggle_similar', $config['grid_toggle_similar']);
        $container->setParameter('lexik_translation.auto_cache_clean', $config['auto_cache_clean']);
        $container->setParameter('lexik_translation.dev_tools.enable', $config['dev_tools']['enable']);
        $container->setParameter('lexik_translation.dev_tools.create_missing', $config['dev_tools']['create_missing']);
        $container->setParameter('lexik_translation.dev_tools.file_format', $config['dev_tools']['file_format']);
        $container->setParameter('lexik_translation.exporter.json.hierarchical_format', $config['exporter']['json_hierarchical_format']);
        $container->setParameter('lexik_translation.exporter.yml.use_tree', $config['exporter']['use_yml_tree']);

        $objectManager = $config['storage']['object_manager'] ?? null;

        $this->buildTranslationStorageDefinition($container, $config['storage']['type'], $objectManager);

        if (true === $config['auto_cache_clean']) {
            $this->buildCacheCleanListenerDefinition($container, $config['auto_cache_clean_interval']);
        }

        if (true === $config['dev_tools']['enable']) {
            $this->buildDevServicesDefinition($container);
        }
    }

    /**
     * @param int $cacheInterval
     */
    public function buildCacheCleanListenerDefinition(ContainerBuilder $container, $cacheInterval): void
    {
        $listener = new Definition();
        $listener->setClass('%lexik_translation.listener.clean_translation_cache.class%');

        $listener->addArgument(new Reference('lexik_translation.translation_storage'));
        $listener->addArgument(new Reference('translator'));
        $listener->addArgument(new Parameter('kernel.cache_dir'));
        $listener->addArgument(new Reference(LocaleManagerInterface::class));
        $listener->addArgument($cacheInterval);

        $listener->addTag('kernel.event_listener', ['event'  => 'kernel.request', 'method' => 'onKernelRequest']);

        $container->setDefinition('lexik_translation.listener.clean_translation_cache', $listener);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $rootDir = '%kernel.project_dir%/vendor/lexik/translation-bundle/Resources/views';

        $container->prependExtensionConfig('twig', [
            'paths' => [
                $rootDir => 'LexikTranslationBundle'
            ]
        ]);
    }

    /**
     * Build the 'lexik_translation.translation_storage' service definition.
     *
     * @param string           $storage
     * @param string           $objectManager
     * @throws \RuntimeException
     */
    protected function buildTranslationStorageDefinition(ContainerBuilder $container, $storage, $objectManager): void
    {
        $container->setParameter('lexik_translation.storage.type', $storage);

        if (StorageInterface::STORAGE_ORM == $storage) {
            $args = [new Reference('doctrine'), $objectManager ?? 'default'];

            // Create XML driver for backward compatibility
            if (class_exists(SimplifiedXmlDriver::class)) {
                $xmlDriverClass = SimplifiedXmlDriver::class;
            } else {
                $xmlDriverClass = $container->getParameter('doctrine.orm.metadata.xml.class');
            }
            $this->createDoctrineMappingDriver($container, 'lexik_translation.orm.metadata.xml', $xmlDriverClass);

            // Create attribute driver for models (MappedSuperclass) that now use PHP attributes
            $this->createDoctrineAttributeDriver($container, 'lexik_translation.orm.metadata.attribute');

            $metadataListener = new Definition();
            $metadataListener->setClass('%lexik_translation.orm.listener.class%');
            $metadataListener->addTag('doctrine.event_listener', ['event' => Events::loadClassMetadata]);

            $container->setDefinition('lexik_translation.orm.listener', $metadataListener);

        } elseif (StorageInterface::STORAGE_MONGODB == $storage) {
            $args = [new Reference('doctrine_mongodb'), $objectManager ?? 'default'];

            $this->createDoctrineMappingDriver($container, 'lexik_translation.mongodb.metadata.xml', '%doctrine_mongodb.odm.metadata.xml.class%');
        } else {
            throw new \RuntimeException(sprintf('Unsupported storage "%s".', $storage));
        }

        $args[] = [
            'trans_unit'  => new Parameter(sprintf('lexik_translation.%s.trans_unit.class', $storage)),
            'translation' => new Parameter(sprintf('lexik_translation.%s.translation.class', $storage)),
            'file'        => new Parameter(sprintf('lexik_translation.%s.file.class', $storage))
        ];

        $storageDefinition = new Definition();
        $storageDefinition->setClass($container->getParameter(sprintf('lexik_translation.%s.translation_storage.class', $storage)));
        $storageDefinition->setArguments($args);
        $storageDefinition->setPublic(true);

        $container->setDefinition('lexik_translation.translation_storage', $storageDefinition);
    }

    /**
     * Add a driver to load mapping of model classes.
     *
     * @param string           $driverId
     * @param string           $driverClass
     */
    protected function createDoctrineMappingDriver(ContainerBuilder $container, $driverId, $driverClass): void
    {
        $driverDefinition = new Definition($driverClass, [
            [dirname(__DIR__) . '/Resources/config/model' => 'Lexik\Bundle\TranslationBundle\Model'],
            SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION, true
        ]);
        $driverDefinition->setPublic(false);

        $container->setDefinition($driverId, $driverDefinition);
    }

    /**
     * Create an attribute driver for models (MappedSuperclass) that use PHP attributes.
     *
     * @param ContainerBuilder $container
     * @param string           $driverId
     */
    protected function createDoctrineAttributeDriver(ContainerBuilder $container, $driverId): void
    {
        // Calculate bundle path using ReflectionClass to get the actual bundle location
        // This works even when the bundle is installed via Composer or symlinked
        $bundleReflection = new \ReflectionClass(LexikTranslationBundle::class);
        $bundleDir = dirname($bundleReflection->getFileName());
        $modelPath = $bundleDir . '/Model';

        // Try to get realpath, but use the calculated path if it fails
        $realModelPath = realpath($modelPath);
        if ($realModelPath) {
            $modelPath = $realModelPath;
        }

        // AttributeDriver constructor expects an array of paths (directories to scan)
        // It will automatically detect classes with #[ORM\MappedSuperclass] or #[ORM\Entity] attributes
        $driverDefinition = new Definition(AttributeDriver::class, [
            [$modelPath]
        ]);
        $driverDefinition->setPublic(false);

        // Always set/override the definition to ensure it exists with correct arguments
        $container->setDefinition($driverId, $driverDefinition);
    }

    /**
     * Load dev tools.
     */
    protected function buildDevServicesDefinition(ContainerBuilder $container): void
    {
        $container
            ->getDefinition('lexik_translation.data_grid.request_handler')
            ->addMethodCall('setProfiler', [new Reference('profiler')]);

        $tokenFinderDefinition = new Definition();
        $tokenFinderDefinition->setClass($container->getParameter('lexik_translation.token_finder.class'));
        $tokenFinderDefinition->setArguments([new Reference('profiler'), new Parameter('lexik_translation.token_finder.limit')]);

        $container->setDefinition($container->getParameter('lexik_translation.token_finder.class'), $tokenFinderDefinition);
    }
}
