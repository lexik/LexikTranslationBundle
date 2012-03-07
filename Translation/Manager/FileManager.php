<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Manager;

use Lexik\Bundle\TranslationBundle\Model\File;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Manager for translations files.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileManager
{
    /**
    * @var Doctrine\Common\Persistence\ObjectManager
    */
    private $objectManager;

    /**
     * @var string
     */
    private $fileclass;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Construct.
     *
     * @param ObjectManager $objectManager
     * @param string $fileclass
     * @param string $rootDir
     */
    public function __construct(ObjectManager $objectManager, $fileclass, $rootDir)
    {
        $this->objectManager = $objectManager;
        $this->fileclass = $fileclass;
        $this->rootDir = str_replace('/app', '', $rootDir);
    }

    /**
     * Returns a translation file according to the given name and path.
     *
     * @param string $name
     * @param string $path
     * @return Lexik\Bundle\TranslationBundle\Model\File
     */
    public function getFor($name, $path)
    {
        $hash = $this->generateHash($name, str_replace($this->rootDir.'/', '', $path));
        $file = $this->objectManager->getRepository($this->fileclass)->findOneBy(array('hash' => $hash));

        if (!($file instanceof File)) {
            $file = $this->create($name, $path);
        }

        return $file;
    }

    /**
     * Create a new file.
     *
     * @param string $name
     * @param string $path
     * @return Lexik\Bundle\TranslationBundle\Model\File
     */
    public function create($name, $path, $flush = false)
    {
        $path = str_replace($this->rootDir.'/', '', $path);

        $class = $this->fileclass;

        $file = new $class();
        $file->setName($name);
        $file->setPath($path);
        $file->setHash($this->generateHash($name, $path));

        $this->objectManager->persist($file);

        if ($flush) {
            $this->objectManager->flush();
        }

        return $file;
    }

    /**
     * Returns the has for the given file.
     *
     * @param string $name
     * @param string $relativePath
     * @return string
     */
    public function generateHash($name, $relativePath)
    {
        return md5($relativePath.'/'.$name);
    }
}