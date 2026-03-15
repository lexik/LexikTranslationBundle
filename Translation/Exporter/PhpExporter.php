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
     * {@inheritdoc}
     */
    public function export(string $file, array $translations): bool
    {
        $phpContent = sprintf("<?php\nreturn %s;", var_export($translations, true));

        $bytes = file_put_contents($file, $phpContent);

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support(string $format): bool
    {
        return ('php' === $format);
    }
}
