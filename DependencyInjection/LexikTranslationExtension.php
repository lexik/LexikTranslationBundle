<?php

namespace Lexik\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
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

        if ($config['storage'] == 'orm') {
            $type = 'Entity';
            $container->setAlias('lexik_translation.storage_manager', 'doctrine.orm.entity_manager');
        } else if ($config['storage'] == 'mongodb') {
            $type = 'Document';
            $container->setAlias('lexik_translation.storage_manager', 'doctrine.odm.mongodb.document_manager');
        } else {
            throw new \RuntimeException(sprintf('Unsupported storage "%s".', $config['storage']));
        }

        // set parameters
        sort($config['managed_locales']);
        $container->setParameter('lexik_translation.managed_locales', $config['managed_locales']);
        $container->setParameter('lexik_translation.fallback_locale', $config['fallback_locale']);
        $container->setParameter('lexik_translation.storage', $config['storage']);
        $container->setParameter('lexik_translation.base_layout', $config['base_layout']);
        $container->setParameter('lexik_translation.grid_input_type', $config['grid_input_type']);

        $container->setParameter('lexik_translation.translator.class', $config['classes']['translator']);
        $container->setParameter('lexik_translation.loader.database.class', $config['classes']['database_loader']);
        $container->setParameter('lexik_translation.trans_unit.class', sprintf('Lexik\Bundle\TranslationBundle\%s\TransUnit', $type));
        $container->setParameter('lexik_translation.translation.class', sprintf('Lexik\Bundle\TranslationBundle\%s\Translation', $type));
        $container->setParameter('lexik_translation.file.class', sprintf('Lexik\Bundle\TranslationBundle\%s\File', $type));

        $this->registerTranslatorConfiguration($config, $container);
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
                    $tmp       = explode('.', $file->getBasename());
                    $format    = array_pop($tmp);
                    $locale    = array_pop($tmp);
                    $domain    = implode('.', $tmp);
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
