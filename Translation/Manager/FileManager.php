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
        $this->rootDir = $rootDir;
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
        $hash = $this->generateHash($name, $this->getFileRelativePath($path));
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
        $path = $this->getFileRelativePath($path);

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
        return md5($relativePath.DIRECTORY_SEPARATOR.$name);
    }

    /**
     * Return the File repository for the current storage.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getFileRepository()
    {
        return $this->objectManager->getRepository($this->fileclass);
    }

    /**
     * Returns the relative according to the kernel.root_dir value.
     *
     * @param string $filePath
     * @return string
     */
    protected function getFileRelativePath($filePath)
    {
        $commonParts = array();
        $rootDirParts = explode(DIRECTORY_SEPARATOR, $this->rootDir);
        $filePathParts = explode(DIRECTORY_SEPARATOR, $filePath);

        $i = 0;
        while ($i < count($rootDirParts)) {
            if ( isset($rootDirParts[$i], $filePathParts[$i]) && $rootDirParts[$i] == $filePathParts[$i] ) {
                $commonParts[] = $rootDirParts[$i];
            }
            $i++;
        }

        $filePath = str_replace(implode(DIRECTORY_SEPARATOR, $commonParts).DIRECTORY_SEPARATOR, '', $filePath);

        for ($i=count($commonParts); $i<count($rootDirParts); $i++) {
            $filePath = '..'.DIRECTORY_SEPARATOR.$filePath;
        }

        return $filePath;
    }
}
