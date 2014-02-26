<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Importer;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Model\Translation;
use Lexik\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;

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
    }

    /**
     * Impoort the given file and return the number of inserted translations.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param boolean                               $forceUpdate  force update of the translations
     * @return int
     */
    public function import(\Symfony\Component\Finder\SplFileInfo $file, $forceUpdate = false)
    {
        $imported = 0;
        list($domain, $locale, $extention) = explode('.', $file->getFilename());

        if (isset($this->loaders[$extention])) {
            $messageCatalogue = $this->loaders[$extention]->load($file->getPathname(), $locale, $domain);

            $translationFile = $this->fileManager->getFor($file->getFilename(), $file->getPath());

            foreach ($messageCatalogue->all($domain) as $key => $content) {
                $transUnit = $this->storage->getTransUnitByKeyAndDomain($key, $domain);

                if (!($transUnit instanceof TransUnit)) {
                    $transUnit = $this->transUnitManager->create($key, $domain);
                }

                $translation = $this->transUnitManager->addTranslation($transUnit, $locale, $content, $translationFile);
                if ($translation instanceof Translation) {
                    $imported++;
                } else if($forceUpdate) {
                    $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content);
                    $imported++;
                }

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
        } else {
            throw new \RuntimeException(sprintf('No load found for "%s" format.', $extention));
        }

        return $imported;
    }
}
