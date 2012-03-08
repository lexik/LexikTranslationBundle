<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Export translations to a PHP file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class PhpExporter implements ExporterInterface
{
    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Translation\Exporter.ExporterInterface::export()
     */
    public function export($file, $translations)
    {
        $rows = array();
        foreach ($translations as $key => $content) {
            $rows[] = sprintf("'%s' => '%s',", $key, str_replace("'", "\'", $content));
        }

        $phpContent = sprintf("<?php\nreturn array(\n    %s\n);", implode("\n    ", $rows));

        $bytes = file_put_contents($file, $phpContent);

        return ($bytes !== false);
    }
}