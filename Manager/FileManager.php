<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Lexik\Bundle\TranslationBundle\Model\File;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Manager for translations files.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FileManager implements FileManagerInterface
{
    /**
     * @var ObjectManager
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
     * @param string        $fileclass
     * @param string        $rootDir
     */
    public function __construct(ObjectManager $objectManager, $fileclass, $rootDir)
    {
        $this->objectManager = $objectManager;
        $this->fileclass = $fileclass;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getByLoalesAndDomains(array $locales, array $domains)
    {
        return $this->objectManager
            ->getRepository($this->fileclass)
            ->findForLoalesAndDomains($locales, $domains);
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
        return md5($relativePath.'/'.$name);
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

        for ($i=count($commonParts); $i<count($rootDirParts); $i++) {
            $filePath = '../'.$filePath;
        }

        return $filePath;
    }
}
