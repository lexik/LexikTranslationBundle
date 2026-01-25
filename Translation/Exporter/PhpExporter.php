<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Export translations to a PHP file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[AsTaggedItem('lexik_translation.exporter', alias: 'php')]
#[AsAlias(id: 'lexik_translation.exporter.php')]
class PhpExporter implements ExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $phpContent = sprintf("<?php\nreturn %s;", var_export($translations, true));

        $bytes = file_put_contents($file, $phpContent);

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('php' == $format);
    }
}
