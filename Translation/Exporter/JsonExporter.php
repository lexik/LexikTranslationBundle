<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Export translations to a Json file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[AsTaggedItem('lexik_translation.exporter', alias: 'json')]
#[AsAlias(id: 'lexik_translation.exporter.json', public: true)]
class JsonExporter implements ExporterInterface
{
    /**
     * @param bool $hierarchicalFormat
     */
    public function __construct(
        #[Autowire('%lexik_translation.exporter.json.hierarchical_format%')]
        private $hierarchicalFormat = false
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $bytes = file_put_contents($file, json_encode($this->hierarchicalFormat ? $this->hierarchicalFormat($translations) : $translations, JSON_PRETTY_PRINT));

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('json' == $format);
    }

    /**
     * @return array
     */
    protected function hierarchicalFormat(array $translations)
    {
        $output = [];
        foreach ($translations as $key => $value) {
            $output = array_merge_recursive($output, $this->converterKeyToArray($key, $value));
        }

        return $output;
    }

    /**
     * @param string $key
     * @return array
     */
    protected function converterKeyToArray($key, mixed $value)
    {
        $keysTrad = preg_split("/\./", $key);

        return $this->convertArrayToArborescence($keysTrad, $value);
    }

    /**
     * @return array
     */
    protected function convertArrayToArborescence(mixed $arrayIn, mixed $endValue)
    {
        $lenArray = is_countable($arrayIn) ? count($arrayIn) : 0;

        if ($lenArray == 0) {
            return $endValue;
        }
        $firstKey = array_key_first($arrayIn);
        $firstValue = $arrayIn[$firstKey];
        unset($arrayIn[$firstKey]);

        return [$firstValue => $this->convertArrayToArborescence($arrayIn, $endValue)];
    }
}
