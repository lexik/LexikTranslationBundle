<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Lexik\Bundle\TranslationBundle\Model\File;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export translations from the database in to files.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ExportTranslationsCommand extends ContainerAwareCommand
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this->setName('lexik:translations:export');
        $this->setDescription('Export translations from the database to files.');
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getContainer()
            ->get('lexik_translation.file.manager')
            ->getFileRepository();

        $fileToExport = $repository->findAll();

        if (count($fileToExport) > 0) {
            foreach ($fileToExport as $file) {
                $this->exportFile($file, $output);
            }
        } else {
            $output->writeln('<comment>No translation\'s files in the database.</comment>');
        }
    }

    /**
     * Get translations to export and export translations into a file.
     *
     * @param File $file
     * @param OutputInterface $output
     */
    protected function exportFile(File $file, OutputInterface $output)
    {
        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');

        $outputFile = sprintf('%s/../%s/%s', $rootDir, $file->getPath(), $file->getName());
        $onlyUpdated = false;

        // we don't write vendors file, translations will be exported in app/Resources/translations
        if (substr($file->getPath(), 0, 6) == 'vendor') {
            $outputFile = sprintf('%s/Resources/translations/%s', $rootDir, $file->getName());
            $onlyUpdated = true;
        }

        $output->writeln(sprintf('<info># Exporting "%s/%s":</info>', $file->getPath(), $file->getName()));

        $translations = $this->getContainer()
            ->get('lexik_translation.trans_unit.manager')
            ->getTransUnitRepository()
            ->getTranslationsForFile($file, $onlyUpdated);

        if (count($translations) > 0) {
            $translations = $this->mergeExistingTranslations($file, $outputFile, $translations);
            $this->doExport($outputFile, $translations, $file->getExtention());
        } else {
            $output->writeln('<comment>No translations to export.</comment>');
        }
    }

    /**
     * If the output file exists we merge existing translations with those from the database.
     *
     * @param File $file
     * @param string $outputFile
     * @param array $translations
     * @return array
     */
    protected function mergeExistingTranslations($file, $outputFile, $translations)
    {
        if (file_exists($outputFile)) {
            $loader = $this->getContainer()->get(sprintf('translation.loader.%s', $file->getExtention()));
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
        $output->writeln(sprintf('<comment>Output file: %s</comment>', $outputFile));
        $output->write(sprintf('<comment>%d translations to export: </comment>', count($translations)));

        $exporterId = sprintf('lexik_translation.exporter.%s', $format);

        if ($this->getContainer()->has($exporterId)) {
            $exporter = $this->getContainer()->get($exporterId);
            $exported = $exporter->export($outputFile, $translations);

            $output->writeln($exported ? '<comment>success</comment>' : '<error>fail</error>');
        } else {
            $output->writeln(sprintf('<error>No exporter found for "%s" extention</error>', $format));
        }
    }
}