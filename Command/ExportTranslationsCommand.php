<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Translation\Exporter\ExporterCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Export translations from the database in to files.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[AsCommand(
    name: 'lexik:translations:export',
    description: 'Export translations from the database to files.',
    help: <<<'HELP'
The <info>%command.name%</info> command exports translations from the database back to translation files.

You can filter the export by locales and domains:

  <info>php %command.full_name% --locales=en,fr --domains=messages</info>

You can also specify a custom export path:

  <info>php %command.full_name% --export-path=/path/to/translations</info>

By default, the command exports all translations. Use <comment>--override</comment> to export only modified translations.
HELP
)]
class ExportTranslationsCommand extends Command
{
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly TranslatorInterface $translator,
        private readonly FileSystem $fileSystem,
        private readonly ExporterCollector $exporterCollector,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {

        $this->addOption(
            'locales', 'l', InputOption::VALUE_OPTIONAL,
            'Only export files for given locales. e.g. "--locales=en,de"', null
        );
        $this->addOption(
            'domains', 'd', InputOption::VALUE_OPTIONAL,
            'Only export files for given domains. e.g. "--domains=messages,validators"', null
        );
        $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Force the output format.', null);
        $this->addOption(
            'override', 'o', InputOption::VALUE_NONE,
            'Only export modified phrases (app/Resources/translations are exported fully anyway)'
        );
        $this->addOption('export-path', 'p', InputOption::VALUE_REQUIRED, 'Export files to given path.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $filesToExport = $this->getFilesToExport();

        if (count($filesToExport) > 0) {
            foreach ($filesToExport as $file) {
                $this->exportFile($file);
            }

            return 0;
        }

        $this->output->writeln('<comment>No translation\'s files in the database.</comment>');

        return 1;
    }

    /**
     * Returns all file to export.
     *
     * @return array
     */
    protected function getFilesToExport()
    {
        $locales = $this->input->getOption('locales') ? explode(',', (string)$this->input->getOption('locales')) : [];
        $domains = $this->input->getOption('domains') ? explode(',', (string)$this->input->getOption('domains')) : [];

        return $this->storage->getFilesByLocalesAndDomains($locales, $domains);
    }

    /**
     * Get translations to export and export translations into a file.
     */
    protected function exportFile(FileInterface $file)
    {
        $rootDir = $this->input->getOption('export-path') ? $this->input->getOption('export-path') . '/' : $this->projectDir;

        $this->output->writeln(sprintf('<info># Exporting "%s/%s":</info>', $file->getPath(), $file->getName()));
        $override = $this->input->getOption('override');

        if (!$this->input->getOption('export-path')) {
            // we only export updated translations in case of the file is located in vendor/
            if ($override) {
                $onlyUpdated = ('Resources/translations' !== $file->getPath());
            } else {
                $onlyUpdated = (str_contains((string)$file->getPath(), 'vendor/'));
            }
        } else {
            $onlyUpdated = !$override;
        }

        $translations = $this->storage->getTranslationsFromFile($file, $onlyUpdated);

        if (count($translations) < 1) {
            $this->output->writeln('<comment>No translations to export.</comment>');

            return;
        }

        $format = $this->input->getOption('format') ?: $file->getExtention();

        // we don't write vendors file, translations will be exported in %kernel.root_dir%/Resources/translations
        if (str_contains((string)$file->getPath(), 'vendor/') || $override) {
            $outputPath = sprintf('%s/Resources/translations', $rootDir);
        } else {
            $outputPath = sprintf('%s/%s', $rootDir, $file->getPath());
        }

        $this->output->writeln(sprintf('<info># OutputPath "%s":</info>', $outputPath));

        // ensure the path exists
        if ($this->input->getOption('export-path')) {
            if (!$this->fileSystem->exists($outputPath)) {
                $this->fileSystem->mkdir($outputPath);
            }
        }

        $outputFile = sprintf('%s/%s.%s.%s', $outputPath, $file->getDomain(), $file->getLocale(), $format);
        $this->output->writeln(sprintf('<info># OutputFile "%s":</info>', $outputFile));

        $translations = $this->mergeExistingTranslations($file, $outputFile, $translations);
        $this->doExport($outputFile, $translations, $format);
    }

    /**
     * If the output file exists we merge existing translations with those from the database.
     *
     * @param FileInterface $file
     * @param string        $outputFile
     * @param array         $translations
     * @return array
     */
    protected function mergeExistingTranslations($file, $outputFile, $translations)
    {
        if (file_exists($outputFile)) {
            $extension = pathinfo($outputFile, PATHINFO_EXTENSION);
            $loader = $this->translator->getLoader($extension);
            $messageCatalogue = $loader->load($outputFile, $file->getLocale(), $file->getDomain());

            $translations = array_merge($messageCatalogue->all($file->getDomain()), $translations);
        }

        return $translations;
    }

    /**
     * Export translations.
     *
     * @param string $outputFile
     * @param array  $translations
     * @param string $format
     */
    protected function doExport($outputFile, $translations, $format)
    {
        $this->output->writeln(sprintf('<comment>Output file: %s</comment>', $outputFile));
        $this->output->write(sprintf('<comment>%d translations to export: </comment>', count($translations)));

        try {
            $exported = $this->exporterCollector->export(
                $format,
                $outputFile,
                $translations
            );

            $this->output->writeln($exported ? '<comment>success</comment>' : '<error>fail</error>');
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('<error>"%s"</error>', $e->getMessage()));
        }
    }
}
