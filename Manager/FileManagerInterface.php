<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Lexik\Bundle\TranslationBundle\Entity\File;
/**
 * File manager interface.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface FileManagerInterface
{
    /**
     * Create a new file.
     *
     * @param string $name
     * @param string $path
     * @return File
     */
    public function create($name, $path, $flush = false);

    /**
     * Returns a translation file according to the given name and path.
     * If path is null, app/Resources/translations will be used as default path.
     *
     * @param string $name
     * @param string $path
     * @return File
     */
    public function getFor($name, $path = null);
}
