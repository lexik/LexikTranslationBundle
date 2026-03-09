<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Translation\Importer\FileImporter;
use LogicException;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Imports translation files content in the database.
 * Only imports files for locales defined in lexik_translation.managed_locales.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Nikola Petkanski <nikola@petkanski.com>
 */
#[AsCommand(
    name: 'lexik:translations:import',
    description: 'Import all translations from flat files (xliff, yml, php) into the database.',
    help: <<<'HELP'
The <info>%command.name%</info> command imports translation files from your project into the database.

By default, the command imports translations from:
  - Application translation files (<comment>translations/</comment> directory)
  - Bundle translation files
  - Component translation files

You can filter the import by locales:

  <info>php %command.full_name% --locales=en,fr</info>

You can also import from a specific path:

  <info>php %command.full_name% --import-path=/path/to/translations</info>

Use <comment>--force</comment> to replace existing translations in the database.
Use <comment>--merge</comment> to merge translations (keeps the latest updatedAt date).
Use <comment>--cache-clear</comment> to remove translation cache files after import.
HELP
)]
class ImportTranslationsCommand extends Command
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LocaleManagerInterface $localeManager,
        private readonly FileImporter $fileImporter,
    ) {
        parent::__construct();
    }

    private ?InputInterface $input = null;

    private ?OutputInterface $output = null;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $this->checkOptions();

        $locales = $this->input->getOption('locales');
        if (empty($locales)) {
            $locales = $this->localeManager->getLocales();
        }

        $domains = $input->getOption('domains') ? explode(',', (string)$input->getOption('domains')) : [];

        $bundleName = $this->input->getArgument('bundle');
        if ($bundleName) {
            $bundle = $this->getApplication()->getKernel()->getBundle($bundleName);
            $this->importBundleTranslationFiles($bundle, $locales, $domains, (bool)$this->input->getOption('globals'));
        } else {
            if (!$this->input->getOption('import-path')) {

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

        return 0;
    }

    /**
     * Checks if given options are compatible.
     */
    protected function checkOptions()
    {
        if ($this->input->getOption('only-vendors') && $this->input->getOption('globals')) {
            throw new LogicException('You cannot use "globals" and "only-vendors" at the same time.');
        }

        if ($this->input->getOption('import-path')
            && ($this->input->getOption('globals')
                || $this->input->getOption('merge')
                || $this->input->getOption('only-vendors'))) {
            throw new LogicException('You cannot use "globals", "merge" or "only-vendors" and "import-path" at the same time.');
        }
    }

    /**
     * @param string $path
     */
    protected function importTranslationFilesFromPath($path, array $locales, array $domains)
    {
        $finder = $this->findTranslationsFiles($path, $locales, $domains, false);
        $this->importTranslationFiles($finder);
    }

    /**
     * Imports Symfony's components translation files.
     */
    protected function importComponentTranslationFiles(array $locales, array $domains)
    {
        $classes = [
            Validation::class              => '/Resources/translations',
            Form::class                    => '/Resources/translations',
            AuthenticationException::class => '/../Resources/translations',
        ];

        $dirs = [];
        foreach ($classes as $namespace => $translationDir) {
            $reflection = new ReflectionClass($namespace);
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
     */
    protected function importAppTranslationFiles(array $locales, array $domains)
    {
        if (Kernel::MAJOR_VERSION >= 4) {
            $translationPath = $this->getApplication()->getKernel()->getProjectDir() . '/translations';
            $finder = $this->findTranslationsFiles($translationPath, $locales, $domains, false);
        } else {
            $finder = $this->findTranslationsFiles($this->getApplication()->getKernel()->getRootDir(), $locales, $domains);
        }
        $this->importTranslationFiles($finder);
    }

    /**
     * Imports translation files form all bundles.
     *
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
     * @param array   $locales
     * @param array   $domains
     * @param boolean $global
     */
    protected function importBundleTranslationFiles(BundleInterface $bundle, $locales, $domains, $global = false)
    {
        if ($global) {
            $kernel = $this->getApplication()->getKernel();
            if (Kernel::MAJOR_VERSION >= 4) {
                $path = $kernel->getProjectDir() . '/app';
            } else {
                $path = $kernel->getRootDir();
            }

            $path .= '/Resources/' . $bundle->getName() . '/translations';

            $this->output->writeln('<info>*** Importing ' . $bundle->getName() . '`s translation files from ' . $path . ' ***</info>');

            $this->output->writeln(sprintf('<info># %s:</info>', $bundle->getName()));
            $finder = $this->findTranslationsFiles($path, $locales, $domains);
            $this->importTranslationFiles($finder);

            return;
        }

        $paths = [
            $bundle->getPath() . '/translations',
            $bundle->getPath() . '/Resources/translations',
        ];

        foreach($paths as $path) {
            $this->output->writeln(sprintf('<info># %s:</info>', $bundle->getName()));
            $finder = $this->findTranslationsFiles($path, $locales, $domains, false);
            $this->importTranslationFiles($finder);
        }
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

        $this->fileImporter->setCaseInsensitiveInsert($this->input->getOption('case-insensitive'));

        foreach ($finder as $file) {
            $this->output->write(sprintf('Importing <comment>"%s"</comment> ... ', $file->getPathname()));
            $number = $this->fileImporter->import($file, $this->input->getOption('force'), $this->input->getOption('merge'));
            $this->output->writeln(sprintf('%d translations', $number));

            $skipped = $this->fileImporter->getSkippedKeys();
            if (count($skipped) > 0) {
                $this->output->writeln(sprintf('    <error>[!]</error> The following keys has been skipped: "%s".', implode('", "', $skipped)));
            }
        }
    }

    /**
     * Return a Finder object if $path has a Resources/translations folder.
     *
     * @param string $path
     * @return Finder
     */
    protected function findTranslationsFiles($path, array $locales, array $domains, $autocompletePath = true)
    {
        $finder = null;

        if (preg_match('#^win#i', PHP_OS)) {
            $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '#', '/', $path);
        }

        if (true === $autocompletePath) {
            $dir = (str_starts_with((string) $path, $this->getApplication()->getKernel()->getProjectDir() . '/Resources')) ? $path : $path . '/Resources/translations';
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
     * @return string
     */
    protected function getFileNamePattern(array $locales, array $domains)
    {
        $formats = $this->translator->getFormats();

        if (count($domains)) {
            $regex = sprintf('/((%s)\.(%s)\.(%s))/', implode('|', $domains), implode('|', $locales), implode('|', $formats));
        } else {
            $regex = sprintf('/(.*\.(%s)\.(%s))/', implode('|', $locales), implode('|', $formats));
        }

        return $regex;
    }
}
