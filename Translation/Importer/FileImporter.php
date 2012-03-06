<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Importer;

use Doctrine\Common\Persistence\ObjectManager;

use Lexik\Bundle\TranslationBundle\Model\TransUnit;
use Lexik\Bundle\TranslationBundle\Model\Translation;
use Lexik\Bundle\TranslationBundle\Translation\TransUnitManager;

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
     * @var octrine\Common\Persistence\ObjectManager
     */
    private $om;

    /**
     * @var Lexik\Bundle\TranslationBundle\Translation\TransUnitManager
     */
    private $transUnitManager;

    /**
     * Construct.
     *
     * @param array $loaders
     * @param ObjectManager $om
     * @param TransUnitManager $transUnitManager
     */
    public function __construct(array $loaders, ObjectManager $om, TransUnitManager $transUnitManager)
    {
        $this->loaders = $loaders;
        $this->om = $om;
        $this->transUnitManager = $transUnitManager;
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
        $serviceId = sprintf('translation.loader.%s', $extention);

        if (isset($this->loaders[$serviceId])) {
            $repository = $this->transUnitManager->getTransUnitRepository();

            $loader = $this->loaders[$serviceId];
            $messageCatalogue = $loader->load($file->getPathname(), $locale, $domain);

            foreach ($messageCatalogue->all() as $domainName => $messages) {
                foreach ($messages as $key => $content) {
                    $transUnit = $repository->findOneBy(array('key' => $key, 'domain' => $domainName));

                    if (!($transUnit instanceof TransUnit)) {
                        $transUnit = $this->transUnitManager->create($key, $domainName);
                    }

                    $translation = $this->transUnitManager->addTranslation($transUnit, $locale, $content);
                    if ($translation instanceof Translation) {
                        $imported++;
                    }
                }

                $this->om->flush();
                $this->om->clear();
            }
        }

        return $imported;
    }
}