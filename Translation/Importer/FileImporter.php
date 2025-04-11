<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Importer;

use Symfony\Component\Finder\SplFileInfo;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;

/**
 * Import a translation file into the database.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileImporter
{
    private bool $caseInsensitiveInsert;

    private array $skippedKeys;

    /**
     * Construct.
     */
    public function __construct(
        private array $loaders,
        private readonly StorageInterface $storage,
        private readonly TransUnitManagerInterface $transUnitManager,
        private readonly FileManagerInterface $fileManager,
    ) {
        $this->caseInsensitiveInsert = false;
        $this->skippedKeys = [];
    }

    /**
     * @param boolean $value
     */
    public function setCaseInsensitiveInsert($value)
    {
        $this->caseInsensitiveInsert = (bool)$value;
    }

    /**
     * @return array
     */
    public function getSkippedKeys()
    {
        return $this->skippedKeys;
    }

    /**
     * Import the given file and return the number of inserted translations.
     *
     * @param boolean $forceUpdate force update of the translations
     * @param boolean $merge merge translations
     * @return int
     */
    public function import(SplFileInfo $file, $forceUpdate = false, $merge = false)
    {
        $this->skippedKeys = [];
        $imported = 0;
        [$domain, $locale, $extension] = explode('.', $file->getFilename());

        if (!isset($this->loaders[$extension])) {
            throw new \RuntimeException(sprintf('No loader found for "%s" format.', $extension));
        }

        $messageCatalogue = $this->loaders[$extension]->load($file->getPathname(), $locale, $domain);

        $translationFile = $this->fileManager->getFor($file->getFilename(), $file->getPath());

        $keys = [];

        foreach ($messageCatalogue->all($domain) as $key => $content) {
            if (!isset($content)) {
                continue; // skip empty translation values
            }

            $normalizedKey = $this->caseInsensitiveInsert ? strtolower((string)$key) : $key;

            if (in_array($normalizedKey, $keys, true)) {
                $this->skippedKeys[] = $key;
                continue; // skip duplicate keys
            }

            $transUnit = $this->storage->getTransUnitByKeyAndDomain($key, $domain);

            if (!($transUnit instanceof TransUnitInterface)) {
                $transUnit = $this->transUnitManager->create($key, $domain);
            }

            $translation = $this->transUnitManager->addTranslation($transUnit, $locale, $content, $translationFile);
            if ($translation instanceof TranslationInterface) {
                $imported++;
            } else {
                if ($forceUpdate) {
                    $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content);
                    if ($translation instanceof Translation) {
                        $translation->setModifiedManually(false);
                    }
                    $imported++;
                } else {
                    if ($merge) {
                        $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content, false, true);
                        if ($translation instanceof TranslationInterface) {
                            $imported++;
                        }
                    }
                }
            }

            $keys[] = $normalizedKey;

            // convert MongoTimestamp objects to time to don't get an error in:
            // Doctrine\ODM\MongoDB\Mapping\Types\TimestampType::convertToDatabaseValue()
            if ($transUnit instanceof TransUnitDocument) {
                $transUnit->convertMongoTimestamp();
            }
        }

        $this->storage->flush();

        // clear only Lexik entities
        foreach (['file', 'trans_unit', 'translation'] as $name) {
            $this->storage->clear($this->storage->getModelClass($name));
        }

        return $imported;
    }
}
