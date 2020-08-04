<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\TranslationBundle\Entity\File;
use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExportToJavascriptService
{
	/** @var EntityManagerInterface */
	private $entityManager;

	/** @var TranslatorInterface */
	private $translator;

	/**
	 * Where is files saved to
	 */
	private $exportPath;

	private $translationsByLocale = [];

	public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, $exportPath)
	{
		$this->entityManager = $entityManager;
		$this->translator = $translator;
		$this->exportPath = $exportPath;
	}

	public function generate()
	{
		if ($this->exportPath === NULL) {
			return;
		}

		$filesToExport = $this->getFilesToExport();

		if (count($filesToExport) > 0) {
			// build translations array by locale
			foreach ($filesToExport as $file) {
				$this->gatherTranslations($file);
			}

			// generate json files
			foreach($this->translationsByLocale as $locale => $translations) {
				$fs = new Filesystem();
				$fs->dumpFile($this->exportPath.DIRECTORY_SEPARATOR.$locale.'.json', \json_encode($translations));
			}

			return 1;
		} else {
			return;
		}
	}
	protected function getFilesToExport()
	{
		return $this->entityManager->getRepository(File::class)->findBy(['exportToJavascript' => TRUE]);
	}

	protected function prefixTranslationKeysWithDomain(FileInterface $file, array $translations)
	{
		$domain = $file->getDomain();

		$newArr = [];
		foreach($translations as $key => $val)
		{
			$newArr[$domain.'.'.$key] = $val;
		}
		return $newArr;
	}

	protected function gatherTranslations(FileInterface $file)
	{
		$rootDir = $this->exportPath;

		$translations = $this->entityManager->getRepository(TransUnit::class)->getTranslationsForFile($file, FALSE);

		$translations = $this->prefixTranslationKeysWithDomain($file, $translations);

		if (!isset($this->translationsByLocale[$file->getLocale()])) {
			$this->translationsByLocale[$file->getLocale()] = $translations;
		} else {
			$this->translationsByLocale[$file->getLocale()] = array_merge($this->translationsByLocale[$file->getLocale()], $translations);
		}
	}
}
