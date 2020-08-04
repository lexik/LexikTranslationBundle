<?php

namespace Lexik\Bundle\TranslationBundle\Command;


use Lexik\Bundle\TranslationBundle\Translation\ExportToJavascriptService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Export translations from the database in to files.
 *
 * @author Ondřej Korouš <ondrej@punkmedia.cz>
 */
class ExportToJavascriptCommand extends Command
{
	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	private $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	private $output;

	/** @var ExportToJavascriptService */
	protected $exportToJavascriptService;

	public function __construct(ExportToJavascriptService $exportToJavascriptService)
	{
		$this->exportToJavascriptService = $exportToJavascriptService;

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('lexik:translations:export-to-javascript');
		$this->setDescription('Export translations from the database to json for use in javascript.');

		//$this->addOption('locales', 'l', InputOption::VALUE_OPTIONAL, 'Only export files for given locales. e.g. "--locales=en,de"', null);
		//$this->addOption('domains', 'd', InputOption::VALUE_OPTIONAL, 'Only export files for given domains. e.g. "--domains=messages,validators"', null);
		/*$this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Force the output format.', null);
		$this->addOption('override', 'o', InputOption::VALUE_NONE, 'Only export modified phrases (app/Resources/translations are exported fully anyway)');
		$this->addOption('export-path', 'p', InputOption::VALUE_REQUIRED, 'Export files to given path.');*/
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;

		$result = $this->exportToJavascriptService->generate();

		if ($result === NULL) {
			$this->output->writeln('<comment>Nothing to export</comment>');
		} else {
			$this->output->writeln('<comment>Exported</comment>');
		}
	}
}
