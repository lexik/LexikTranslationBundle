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
     */
    public function export(string $file, array $translations): bool;

    /**
     * Returns true if this exporter support the given format.
     */
    public function support(string $format): bool;
}
