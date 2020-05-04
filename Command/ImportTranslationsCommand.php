<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Imports translation files content in the database.
 * Only imports files for locales defined in lexik_translation.managed_locales.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Nikola Petkanski <nikola@petkanski.com>
 */
class ImportTranslationsCommand extends Command
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $container;

    /**
     * ImportTranslationsCommand constructor.
     *
     * @param TranslatorInterface $translator
     * @param Container           $container
     */
    public function __construct(TranslatorInterface $translator, Container $container)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->container = $container;
    }

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
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
        $this->addOption('import-path', 'p', InputOption::VALUE_REQUIRED, 'Search for translations at given path');
        $this->addOption('only-vendors', 'o', InputOption::VALUE_NONE, 'Import from vendors only');

        $this->addArgument('bundle', InputArgument::OPTIONAL, 'Import translations for this specific bundle.', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->checkOptions();

        $locales = $this->input->getOption('locales');
        if (empty($locales)) {
            $locales = $this->container->get('lexik_translation.locale.manager')->getLocales();
        }

        $domains = $input->getOption('domains') ? explode(',', $input->getOption('domains')) : array();

        $bundleName = $this->input->getArgument('bundle');
        if ($bundleName) {
            $bundle = $this->getApplication()->getKernel()->getBundle($bundleName);

            if (Kernel::VERSION_ID < 40000 && null !== $bundle->getParent()) {
                // due to symfony's bundle inheritance if a bundle has a parent it is fetched first.
                // so we tell getBundle to NOT fetch the first if a parent is present
                $bundles = $this->getApplication()->getKernel()->getBundle($bundle->getParent(), false);
                $bundle = $bundles[1];
                $this->output->writeln('<info>Using: ' . $bundle->getName() . ' as bundle to lookup translations files for.');
            }

            $this->importBundleTranslationFiles($bundle, $locales, $domains, (bool) $this->input->getOption('globals'));

        } else if(!$this->input->getOption('import-path')) {

            if (!$this->input->getOption('merge') && !$this->input->getOption('only-vendors')) {
                $this->output->writeln('<info>*** Importing application translation files ***</info>');
                $this->importAppTranslationFiles($locales, $domains);
            }

            if ($this->input->getOption('globals')) {
                $this->importBundlesTranslationFiles($locales, $domains, true);
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

        $importPath = $this->input->getOption('import-path');
        if (!empty($importPath)) {
            $this->output->writeln(sprintf('<info>*** Importing translations from path "%s" ***</info>', $importPath));
            $this->importTranslationFilesFromPath($importPath, $locales, $domains);
        }

        if ($this->input->getOption('cache-clear')) {
            $this->output->writeln('<info>Removing translations cache files ...</info>');
            $this->translator->removeLocalesCacheFiles($locales);
        }

        return 1;
    }

    /**
     * Checks if given options are compatible.
     */
    protected function checkOptions()
    {
        if ($this->input->getOption('only-vendors') && $this->input->getOption('globals')) {
            throw new \LogicException('You cannot use "globals" and "only-vendors" at the same time.');
        }

        if ($this->input->getOption('import-path')
            && ($this->input->getOption('globals')
                || $this->input->getOption('merge')
                || $this->input->getOption('only-vendors'))) {
            throw new \LogicException('You cannot use "globals", "merge" or "only-vendors" and "import-path" at the same time.');
        }
    }

    /**
     * @param string $path
     * @param array  $locales
     * @param array  $domains
     */
    protected function importTranslationFilesFromPath($path, array $locales, array $domains)
    {
        $finder = $this->findTranslationsFiles($path, $locales, $domains, false);
        $this->importTranslationFiles($finder);
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
            'Symfony\Component\Validator\Validation'                            => '/Resources/translations',
            'Symfony\Component\Form\Form'                                       => '/Resources/translations',
            'Symfony\Component\Security\Core\Exception\AuthenticationException' => '/../Resources/translations',
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
        if (Kernel::MAJOR_VERSION >= 4) {
            $translationPath = $this->getApplication()->getKernel()->getProjectDir().'/translations';
            $finder = $this->findTranslationsFiles($translationPath, $locales, $domains, false);
        } else {
            $finder = $this->findTranslationsFiles($this->getApplication()->getKernel()->getRootDir(), $locales, $domains);
        }
        $this->importTranslationFiles($finder);
    }

    /**
     * Imports translation files form all bundles.
     *
     * @param array $locales
     * @param array $domains
     * @param boolean $global
     */
    protected function importBundlesTranslationFiles(array $locales, array $domains, $global = false)
    {
        $bundles = $this->getApplication()->getKernel()->getBundles();

        foreach ($bundles as $bundle) {
            $this->importBundleTranslationFiles($bundle, $locales, $domains, $global);
        }
    }

    /**
     * Imports translation files form the specific bundles.
     *
     * @param BundleInterface $bundle
     * @param array           $locales
     * @param array           $domains
     * @param boolean         $global
     */
    protected function importBundleTranslationFiles(BundleInterface $bundle, $locales, $domains, $global = false)
    {
        $path = $bundle->getPath();
        if ($global) {
            $path = $this->getApplication()->getKernel()->getRootDir() . '/Resources/' . $bundle->getName() . '/translations';
            $this->output->writeln('<info>*** Importing ' . $bundle->getName() . '`s translation files from ' . $path . ' ***</info>');
        }

        $this->output->writeln(sprintf('<info># %s:</info>', $bundle->getName()));
        $finder = $this->findTranslationsFiles($path, $locales, $domains);
        $this->importTranslationFiles($finder);
    }

    /**
     * Imports some translations files.
     *
     * @param Finder $finder
     */
    protected function importTranslationFiles($finder)
    {
        if (!$finder instanceof Finder) {
            $this->output->writeln('No file to import');

            return;
        }

        $importer = $this->container->get('lexik_translation.importer.file');
        $importer->setCaseInsensitiveInsert($this->input->getOption('case-insensitive'));

        foreach ($finder as $file) {
            $this->output->write(sprintf('Importing <comment>"%s"</comment> ... ', $file->getPathname()));
            $number = $importer->import($file, $this->input->getOption('force'), $this->input->getOption('merge'));
            $this->output->writeln(sprintf('%d translations', $number));

            $skipped = $importer->getSkippedKeys();
            if (count($skipped) > 0) {
                $this->output->writeln(sprintf('    <error>[!]</error> The following keys has been skipped: "%s".', implode('", "', $skipped)));
            }
        }
    }

    /**
     * Return a Finder object if $path has a Resources/translations folder.
     *
     * @param string $path
     * @param array  $locales
     * @param array  $domains
     * @return \Symfony\Component\Finder\Finder
     */
    protected function findTranslationsFiles($path, array $locales, array $domains, $autocompletePath = true)
    {
        $finder = null;

        if (preg_match('#^win#i', PHP_OS)) {
            $path = preg_replace('#'. preg_quote(DIRECTORY_SEPARATOR, '#') .'#', '/', $path);
        }

        if (true === $autocompletePath) {
            $dir = (0 === strpos($path, $this->getApplication()->getKernel()->getProjectDir() . '/Resources')) ? $path : $path . '/Resources/translations';
        } else {
            $dir = $path;
        }

        $this->output->writeln('<info>*** Using dir ' . $dir . ' to lookup translation files. ***</info>');

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
        $formats = $this->container->get('lexik_translation.translator')->getFormats();

        if (count($domains)) {
            $regex = sprintf('/((%s)\.(%s)\.(%s))/', implode('|', $domains), implode('|', $locales), implode('|', $formats));
        } else {
            $regex = sprintf('/(.*\.(%s)\.(%s))/', implode('|', $locales), implode('|', $formats));
        }

        return $regex;
    }
}
