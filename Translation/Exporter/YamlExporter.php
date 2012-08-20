<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

use Symfony\Component\Yaml\Dumper;

/**
 * Export translations to a Yaml file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class YamlExporter implements ExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $ymlDumper = new Dumper();
        $ymlContent = $ymlDumper->dump($translations, 1);

        $bytes = file_put_contents($file, $ymlContent);

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('yml' == $format);
    }
}