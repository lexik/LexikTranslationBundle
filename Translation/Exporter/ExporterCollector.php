<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Exporter collector.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ExporterCollector
{
    /**
     * @var array
     */
    private $exporters;

    /**
     *
     * @param string $id
     * @param ExporterInterface $exporter
     */
    public function addExporter($id, ExporterInterface $exporter)
    {
        $this->exporters[$id] = $exporter;
    }

    /**
     * Returns an exporter that support the given format.
     *
     * @param string $format
     * @return ExporterInterface
     */
    public function getByFormat($format)
    {
        $exporter = null;
        $i = 0;
        $ids = array_keys($this->exporters);

        while ($i<count($ids) && null === $exporter) {
            if ($this->exporters[$ids[$i]]->support($format)) {
                $exporter = $this->exporters[$ids[$i]];
            }
            $i++;
        }

        if (null === $exporter) {
            throw new \RuntimeException(sprintf('No exporter found for "%s" format.', $format));
        }

        return $exporter;
    }
}