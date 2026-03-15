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
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string $rootDir,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getFor(string $name, ?string $path = null): FileInterface
    {
        if (null === $path) {
            $path = sprintf('%s/Resources/translations', $this->rootDir);
        }

        $hash = $this->generateHash($name, $this->getFileRelativePath($path));
        $file = $this->storage->getFileByHash($hash);

        return $file instanceof FileInterface ? $file : $this->create($name, $path);

    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, string $path, bool $flush = false): FileInterface
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
     */
    protected function generateHash(string $name, string $relativePath): string
    {
        return md5($relativePath . DIRECTORY_SEPARATOR . $name);
    }

    /**
     * Returns the relative according to the kernel.root_dir value.
     */
    protected function getFileRelativePath(string $filePath): string
    {
        $commonParts = [];

        // replace window \ to work with /
        $rootDir = (str_contains($this->rootDir, '\\')) ? str_replace('\\', '/', $this->rootDir) : $this->rootDir;

        $antiSlash = false;
        if (str_contains($filePath, '\\')) {
            $filePath = str_replace('\\', '/', $filePath);
            $antiSlash = true;
        }

        $rootDirParts = explode('/', $rootDir);
        $filePathParts = explode('/', $filePath);

        $i = 0;
        while ($i < count($rootDirParts)) {
            if (isset($rootDirParts[$i], $filePathParts[$i]) && $rootDirParts[$i] === $filePathParts[$i]) {
                $commonParts[] = $rootDirParts[$i];
            }
            $i++;
        }

        $filePath = str_replace(implode('/', $commonParts) . '/', '', $filePath);

        $nbCommonParts = count($commonParts);
        $nbRootParts = count($rootDirParts);

        for ($i = $nbCommonParts; $i < $nbRootParts; $i++) {
            $filePath = '../' . $filePath;
        }

        return $antiSlash ? str_replace('/', '\\', $filePath) : $filePath;
    }
}
