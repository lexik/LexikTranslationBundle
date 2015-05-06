<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Importer;

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
    /**
     * @var array
     */
    private $loaders;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TransUnitManagerInterface
     */
    private $transUnitManager;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    /**
     * @var boolean
     */
    private $caseInsensitiveInsert;

    /**
     * @var array
     */
    private $skippedKeys;

    /**
     * Construct.
     *
     * @param array                     $loaders
     * @param StorageInterface          $storage
     * @param TransUnitManagerInterface $transUnitManager
     * @param FileManagerInterface      $fileManager
     */
    public function __construct(array $loaders, StorageInterface $storage, TransUnitManagerInterface $transUnitManager, FileManagerInterface $fileManager)
    {
        $this->loaders = $loaders;
        $this->storage = $storage;
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
        $this->caseInsensitiveInsert = false;
        $this->skippedKeys = array();
    }

    /**
     * @param boolean $value
     */
    public function setCaseInsensitiveInsert($value)
    {
        $this->caseInsensitiveInsert = (bool) $value;
    }

    /**
     * @return array
     */
    public function getSkippedKeys()
    {
        return $this->skippedKeys;
    }

    /**
     * Impoort the given file and return the number of inserted translations.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param boolean                               $forceUpdate  force update of the translations
     * @param boolean                               $merge        merge translations
     * @return int
     */
    public function import(\Symfony\Component\Finder\SplFileInfo $file, $forceUpdate = false, $merge = false)
    {
        $this->skippedKeys = array();
        $imported = 0;
        list($domain, $locale, $extention) = explode('.', $file->getFilename());

        if (!isset($this->loaders[$extention])) {
            throw new \RuntimeException(sprintf('No load found for "%s" format.', $extention));
        }

        $messageCatalogue = $this->loaders[$extention]->load($file->getPathname(), $locale, $domain);

        $translationFile = $this->fileManager->getFor($file->getFilename(), $file->getPath());

        $keys = array();

        foreach ($messageCatalogue->all($domain) as $key => $content) {
            if (!isset($content)) {
                continue; // skip empty translation values
            }

            $normalizedKey = $this->caseInsensitiveInsert ? strtolower($key) : $key;

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

            } else if($forceUpdate) {
                $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content);
                $imported++;

            } else if($merge) {
                $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content, false, true);
                if ($translation instanceof TranslationInterface) {
                    $imported++;
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
        foreach (array('file', 'trans_unit', 'translation') as $name) {
            $this->storage->clear($this->storage->getModelClass($name));
        }

        return $imported;
    }
}
