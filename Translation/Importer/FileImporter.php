<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Importer;

use Doctrine\Common\Persistence\ObjectManager;

use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Model\Translation;
use Lexik\Bundle\TranslationBundle\Translation\Manager\FileManager;
use Lexik\Bundle\TranslationBundle\Translation\Manager\TransUnitManager;

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
     * @var Doctrine\Common\Persistence\ObjectManager
     */
    private $om;

    /**
     * @var Lexik\Bundle\TranslationBundle\Translation\Manager\TransUnitManager
     */
    private $transUnitManager;

    /**
     * @var Lexik\Bundle\TranslationBundle\Translation\Manager\FileManager
     */
    private $fileManager;

    /**
     * Construct.
     *
     * @param array $loaders
     * @param ObjectManager $om
     * @param TransUnitManager $transUnitManager
     * @param FileManager $fileManager
     */
    public function __construct(array $loaders, ObjectManager $om, TransUnitManager $transUnitManager, FileManager $fileManager)
    {
        $this->loaders = $loaders;
        $this->om = $om;
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
    }

    /**
     * Impoort the given file and return the number of inserted translations.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return int
     */
    public function import(\Symfony\Component\Finder\SplFileInfo $file)
    {
        $imported = 0;
        list($domain, $locale, $extention) = explode('.', $file->getFilename());

        if (isset($this->loaders[$extention])) {
            $messageCatalogue = $this->loaders[$extention]->load($file->getPathname(), $locale, $domain);

            $translationFile = $this->fileManager->getFor($file->getFilename(), $file->getPath());

            foreach ($messageCatalogue->all($domain) as $key => $content) {
                $transUnit = $this->transUnitManager->findOneByKeyAndDomain($key, $domain);

                if (!($transUnit instanceof TransUnit)) {
                    $transUnit = $this->transUnitManager->create($key, $domain);
                }

                $translation = $this->transUnitManager->addTranslation($transUnit, $locale, $content, $translationFile);
                if ($translation instanceof Translation) {
                    $imported++;
                }

                // convert MongoTimestamp objects to time to don't get an error in:
                // Doctrine\ODM\MongoDB\Mapping\Types\TimestampType::convertToDatabaseValue()
                if ($transUnit instanceof TransUnitDocument) {
                    $transUnit->convertMongoTimestamp();
                }
            }

            $this->om->flush();
            $this->om->clear();
        } else {
            throw new \RuntimeException(sprintf('No load found for "%s" format.', $serviceId));
        }

        return $imported;
    }
}