<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Imports translation files content in the database.
 * Only imports files for locales defined in lexik_translation.managed_locales.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Nikola Petkanski <nikola@petkanski.com>
 */
class ImportTranslationsCommand extends ContainerAwareCommand
{
    /**
     * @var Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('lexik:translations:import');
        $this->setDescription('Import all translations from flat files (xliff, yml, php) into the database.');

        $this->addOption('cache-clear', 'c', InputOption::VALUE_NONE, 'Remove translations cache files for managed locales.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force import, replace database content.');
        $this->addOption('globals', 'g', InputOption::VALUE_NONE, 'Import only globals (app/Resources/translations.');
        $this->addOption('locales', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Import only for these locales, instead of using the managed locales.');
        $this->addOption('domains', 'd', InputOption::VALUE_OPTIONAL, 'Only imports files for given domains (comma separated).');
        $this->addOption('case-insensitive', 'i', InputOption::VALUE_NONE, 'Process translation as lower case to avoid duplicate entry errors.');
        $this->addOption('merge', 'm', InputOption::VALUE_NONE, 'Merge translation (use ones with latest updatedAt date).');

        $this->addArgument('bundle', InputArgument::OPTIONAL,'Import translations for this specific bundle.', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $locales = $this->input->getOption('locales');
        if (empty($locales)) {
            $locales = $this->getContainer()->getParameter('lexik_translation.managed_locales');
        }

        $domains = $input->getOption('domains') ? explode(',', $input->getOption('domains')) : array();

        $bundleName = $this->input->getArgument('bundle');
        if ($bundleName) {
            $bundle = $this->getApplication()->getKernel()->getBundle($bundleName);
            $this->importBundleTranslationFiles($bundle, $locales, $domains);
        } else {
            if (!$this->input->getOption('merge')) {
                $this->output->writeln('<info>*** Importing application translation files ***</info>');
                $this->importAppTranslationFiles($locales, $domains);
            }

            if (!$this->input->getOption('globals')) {
                $this->output->writeln('<info>*** Importing bundles translation files ***</info>');
                $this->importBundlesTranslationFiles($locales, $domains);

                $this->output->writeln('<info>*** Importing component translation files ***</info>');
                $this->importComponentTranslationFiles($locales, $domains);
            }

            if ($this->input->getOption('merge')) {
                $this->output->writeln('<info>*** Importing application translation files ***</info>');
                $this->importAppTranslationFiles($locales, $domains);
            }
        }

        if ($this->input->getOption('cache-clear')) {
            $this->output->writeln('<info>Removing translations cache files ...</info>');
            $this->removeTranslationCache();
        }
    }

    /**
     * Imports Symfony's components translation files.
     *
     * @param array $locales
     * @param array $domains
     */
    protected function importComponentTranslationFiles(array $locales, array $domains)
    {
        $classes = array(
            'Symfony\Component\Validator\Validator' => '/Resources/translations',
            'Symfony\Component\Form\Form' => '/Resources/translations',
            'Symfony\Component\Security\Core\Exception\AuthenticationException' => '/../../Resources/translations',
        );

        $dirs = array();
        foreach ($classes as $namespace => $translationDir) {
            $reflection = new \ReflectionClass($namespace);
            $dirs[] = dirname($reflection->getFilename()) . $translationDir;
        }

        $finder = new Finder();
        $finder->files()
            ->name($this->getFileNamePattern($locales, $domains))
            ->in($dirs);

        $this->importTranslationFiles($finder->count() > 0 ? $finder : null);
    }

    /**
     * Imports application translation files.
     *
     * @param array $locales
     * @param array $domains
     */
    protected function importAppTranslationFiles(array $locales, array $domains)
    {
        $finder = $this->findTranslationsFiles($this->getApplication()->getKernel()->getRootDir(), $locales, $domains);
        $this->importTranslationFiles($finder);
    }

    /**
     * Imports translation files form all bundles.
     *
     * @param array $locales
     * @param array $domains
     */
    protected function importBundlesTranslationFiles(array $locales, array $domains)
    {
        $bundles = $this->getApplication()->getKernel()->getBundles();

        foreach ($bundles as $bundle) {
            $this->importBundleTranslationFiles($bundle, $locales, $domains);
        }
    }

    /**
     * Imports translation files form the specific bundles.
     *
     * @param BundleInterface $bundle
     * @param array           $locales
     * @param array           $domains
     */
    protected function importBundleTranslationFiles(BundleInterface $bundle, $locales, $domains)
    {
        $this->output->writeln(sprintf('<info># %s:</info>', $bundle->getName()));
        $finder = $this->findTranslationsFiles($bundle->getPath(), $locales, $domains);
        $this->importTranslationFiles($finder);
    }

    /**
     * Imports some translations files.
     *
     * @param Finder $finder
     */
    protected function importTranslationFiles($finder)
    {
        if ($finder instanceof Finder) {
            $importer = $this->getContainer()->get('lexik_translation.importer.file');
            $importer->setCaseInsensitiveInsert($this->input->getOption('case-insensitive'));

            foreach ($finder as $file)  {
                $this->output->write(sprintf('Importing <comment>"%s"</comment> ... ', $file->getPathname()));
                $number = $importer->import($file, $this->input->getOption('force'), $this->input->getOption('merge'));
                $this->output->writeln(sprintf('%d translations', $number));

                $skipped = $importer->getSkippedKeys();
                if (count($skipped) > 0) {
                    $this->output->writeln(sprintf('    <error>[!]</error> The following keys have been skipped: "%s".', implode('", "', $skipped)));
                }
            }
        } else {
            $this->output->writeln('No file to import');
        }
    }

    /**
     * Return a Finder object if $path has a Resources/translations folder.
     *
     * @param string $path
     * @param array  $locales
     * @param array  $domains
     * @return Symfony\Component\Finder\Finder
     */
    protected function findTranslationsFiles($path, array $locales, array $domains)
    {
        $finder = null;

        if (preg_match('#^win#i', PHP_OS)) {
            $path = preg_replace('#'. preg_quote(DIRECTORY_SEPARATOR, '#') .'#', '/', $path);
        }

        $dir = $path.'/Resources/translations';

        if (is_dir($dir)) {
            $finder = new Finder();
            $finder->files()
                ->name($this->getFileNamePattern($locales, $domains))
                ->in($dir);
        }

        return (null !== $finder && $finder->count() > 0) ? $finder : null;
    }

    /**
     * @param array $locales
     * @param array $domains
     * @return string
     */
    protected function getFileNamePattern(array $locales, array $domains)
    {
        $formats = $this->getContainer()->get('lexik_translation.translator')->getFormats();

        if (count($domains)) {
            $regex = sprintf('/((%s)\.(%s)\.(%s))/', implode('|', $domains), implode('|', $locales), implode('|', $formats));
        } else {
            $regex = sprintf('/(.*\.(%s)\.(%s))/', implode('|', $locales), implode('|', $formats));
        }

        return $regex;
    }

    /**
     * Remove translation cache files managed locales.
     */
    protected function removeTranslationCache()
    {
        $locales = $this->getContainer()->getParameter('lexik_translation.managed_locales');
        $this->getContainer()->get('translator')->removeLocalesCacheFiles($locales);
    }
}
