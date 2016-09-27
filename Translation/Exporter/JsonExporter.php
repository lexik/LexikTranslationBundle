<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Export translations to a Json file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class JsonExporter implements ExporterInterface
{
    private $hierachicalFormat;

    /**
     * @param bool $hierachicalFormat
     */
    public function __construct($hierachicalFormat = false)
    {
        $this->hierachicalFormat = $hierachicalFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $bytes = file_put_contents($file, json_encode($this->hierachicalFormat ? $this->hierachicalFormat($translations) : $translations, JSON_PRETTY_PRINT));

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('json' == $format);
    }

    protected function hierachicalFormat($translations)
    {
        $output = array();
        foreach ($translations as $key => $value) {
            $output = array_merge_recursive($output, $this->converterKeyToArray($key, $value));
        }
        return $output;
    }

    protected function converterKeyToArray($key, $value)
    {
        $keysTrad = preg_split("/\./", $key);

        return $this->convertArrayToArborescence($keysTrad, $value);
    }

    protected function convertArrayToArborescence($arrayIn, $endValue)
    {
        $lenArray = count($arrayIn);

        if ($lenArray == 0) {
            return $endValue;
        }

        reset($arrayIn);
        $firstKey = key($arrayIn);
        $firstValue = $arrayIn[$firstKey];
        unset($arrayIn[$firstKey]);

        return array($firstValue => $this->convertArrayToArborescence($arrayIn, $endValue));

    }
}
