<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Exporter interface.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface ExporterInterface
{
    /**
     * Export translations in to the given file.
     *
     * @param string $file
     * @param array $translations
     * @return boolean
     */
    public function export($file, $translations);
}