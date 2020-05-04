<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Export translations from the database in to files.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ExportTranslationsCommand extends Command
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct();
        $this->container = $container;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('lexik:translations:export');
        $this->setDescription('Export translations from the database to files.');

        $this->addOption('locales', 'l', InputOption::VALUE_OPTIONAL, 'Only export files for given locales. e.g. "--locales=en,de"', null);
        $this->addOption('domains', 'd', InputOption::VALUE_OPTIONAL, 'Only export files for given domains. e.g. "--domains=messages,validators"', null);
        $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Force the output format.', null);
        $this->addOption('override', 'o', InputOption::VALUE_NONE, 'Only export modified phrases (app/Resources/translations are exported fully anyway)');
        $this->addOption('export-path', 'p', InputOption::VALUE_REQUIRED, 'Export files to given path.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $filesToExport = $this->getFilesToExport();

        if (count($filesToExport) > 0) {
            foreach ($filesToExport as $file) {
                $this->exportFile($file);
            }
        } else {
            $this->output->writeln('<comment>No translation\'s files in the database.</comment>');
        }
    }

    /**
     * Returns all file to export.
     *
     * @return array
     */
    protected function getFilesToExport()
    {
        $locales = $this->input->getOption('locales') ? explode(',', $this->input->getOption('locales')) : array();
        $domains = $this->input->getOption('domains') ? explode(',', $this->input->getOption('domains')) : array();

        return $this->container
            ->get('lexik_translation.translation_storage')
            ->getFilesByLocalesAndDomains($locales, $domains);
    }

    /**
     * Get translations to export and export translations into a file.
     *
     * @param FileInterface $file
     */
    protected function exportFile(FileInterface $file)
    {
        $rootDir = $this->input->getOption('export-path') ? $this->input->getOption('export-path') . '/' : $this->getContainer()->getParameter('kernel.project_dir');

        $this->output->writeln(sprintf('<info># Exporting "%s/%s":</info>', $file->getPath(), $file->getName()));
        $override = $this->input->getOption('override');

        if (!$this->input->getOption('export-path')) {
            // we only export updated translations in case of the file is located in vendor/
            if ($override) {
                $onlyUpdated = ('Resources/translations' !== $file->getPath());
            } else {
                $onlyUpdated = (false !== strpos($file->getPath(), 'vendor/'));
            }
        } else {
            $onlyUpdated = !$override;
        }

        $translations = $this->getContainer()
            ->get('lexik_translation.translation_storage')
            ->getTranslationsFromFile($file, $onlyUpdated);

        if (count($translations) < 1) {
            $this->output->writeln('<comment>No translations to export.</comment>');

            return;
        }

        $format = $this->input->getOption('format') ? $this->input->getOption('format') : $file->getExtention();

        // we don't write vendors file, translations will be exported in %kernel.project_dir%/Resources/translations
        if (false !== strpos($file->getPath(), 'vendor/') || $override) {
            $outputPath = sprintf('%s/Resources/translations', $rootDir);
        } else {
            $outputPath = sprintf('%s/%s', $rootDir, $file->getPath());
        }

        $this->output->writeln(sprintf('<info># OutputPath "%s":</info>', $outputPath));

        // ensure the path exists
        if ($this->input->getOption('export-path')) {
            /** @var Filesystem $fs */
            $fs = $this->container->get('filesystem');
            if (!$fs->exists($outputPath)) {
                $fs->mkdir($outputPath);
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
     * @param string $outputFile
     * @param array $translations
     * @return array
     */
    protected function mergeExistingTranslations($file, $outputFile, $translations)
    {
        if (file_exists($outputFile)) {
            $extension = pathinfo($outputFile, PATHINFO_EXTENSION);
            $loader = $this->container->get('lexik_translation.translator')->getLoader($extension);
            $messageCatalogue = $loader->load($outputFile, $file->getLocale(), $file->getDomain());

            $translations = array_merge($messageCatalogue->all($file->getDomain()), $translations);
        }

        return $translations;
    }

    /**
     * Export translations.
     *
     * @param string $outputFile
     * @param array $translations
     * @param string $format
     */
    protected function doExport($outputFile, $translations, $format)
    {
        $this->output->writeln(sprintf('<comment>Output file: %s</comment>', $outputFile));
        $this->output->write(sprintf('<comment>%d translations to export: </comment>', count($translations)));

        try {
            $exported = $this->container->get('lexik_translation.exporter_collector')->export(
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
