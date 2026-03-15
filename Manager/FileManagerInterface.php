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
     */
    public function create(string $name, string $path, bool $flush = false): FileInterface;

    /**
     * Returns a translation file according to the given name and path.
     * If path is null, app/Resources/translations will be used as default path.
     */
    public function getFor(string $name, ?string $path = null): FileInterface;
}
