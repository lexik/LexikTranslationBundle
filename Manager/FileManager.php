<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

/**
 * Manager for translations files.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Nikola Petkanski <nikola@petkanski.com>
 */
class FileManager implements FileManagerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Construct.
     *
     * @param StorageInterface $storage
     * @param string           $rootDir
     */
    public function __construct(StorageInterface $storage, $rootDir)
    {
        $this->storage = $storage;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getFor($name, $path)
    {
        $hash = $this->generateHash($name, $this->getFileRelativePath($path));
        $file = $this->storage->getFileByHash($hash);

        if (!($file instanceof FileInterface)) {
            $file = $this->create($name, $path);
        }

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function create($name, $path, $flush = false)
    {
        $path = $this->getFileRelativePath($path);

        $class = $this->storage->getModelClass('file');

        $file = new $class();
        $file->setName($name);
        $file->setPath($path);
        $file->setHash($this->generateHash($name, $path));

        $this->storage->persist($file);

        if ($flush) {
            $this->storage->flush();
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
    protected function generateHash($name, $relativePath)
    {
        return md5($relativePath.DIRECTORY_SEPARATOR.$name);
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

        // %kernel.root_dir% is always using "/" as directory separator
        $rootDirParts = explode('/', $this->rootDir);
        $filePathParts = explode('/', $filePath);

        $i = 0;
        while ($i < count($rootDirParts)) {
            if ( isset($rootDirParts[$i], $filePathParts[$i]) && $rootDirParts[$i] == $filePathParts[$i] ) {
                $commonParts[] = $rootDirParts[$i];
            }
            $i++;
        }

        $filePath = str_replace(implode('/', $commonParts).'/', '', $filePath);

        $nbCommonParts = count($commonParts);
        $nbRootParts = count($rootDirParts);

        for ($i=$nbCommonParts; $i<$nbRootParts; $i++) {
            $filePath = '../'.$filePath;
        }

        return $filePath;
    }
}
