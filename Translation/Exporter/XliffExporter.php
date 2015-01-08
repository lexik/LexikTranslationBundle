<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Export translations to a Xliff file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class XliffExporter implements ExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $dom = $this->createXmlDocument();
        $bodyNode = $this->addRootNodes($dom);

        $id = 1;
        foreach ($translations as $key => $content) {
            $bodyNode->appendChild($this->createTranslationNode($dom, $id, $key, $content));
            $id++;
        }

        $bytes = file_put_contents($file, $dom->saveXML());

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('xlf' == $format || 'xliff' == $format);
    }

    /**
     * Create a new xml document.
     *
     * @return \DOMDocument
     */
    protected function createXmlDocument()
    {
        $dom = new \DOMDocument('1.0');
        $dom->formatOutput = true;

        return $dom;
    }

    /**
     * Add root nodes to a document.
     *
     * @param \DOMDocument $dom
     * @return \DOMElement
     */
    protected function addRootNodes(\DOMDocument $dom)
    {
        $xliff = $dom->appendChild($dom->createElement('xliff'));
        $xliff->appendChild(new \DOMAttr('version', '1.2'));
        $xliff->appendChild(new \DOMAttr('xmlns', 'urn:oasis:names:tc:xliff:document:1.2'));

        $fileNode = $xliff->appendChild($dom->createElement('file'));
        $fileNode->appendChild(new \DOMAttr('source-language', 'en'));
        $fileNode->appendChild(new \DOMAttr('datatype', 'plaintext'));
        $fileNode->appendChild(new \DOMAttr('original', 'file.ext'));

        $bodyNode =  $fileNode->appendChild($dom->createElement('body'));

        return $bodyNode;
    }

    /**
     * Create a new trans-unit node.
     *
     * @param \DOMDocument $dom
     * @param int $id
     * @param string $key
     * @param string $value
     * @return \DOMElement
     */
    protected function createTranslationNode(\DOMDocument $dom, $id, $key, $value)
    {
        $translationNode = $dom->createElement('trans-unit');
        $translationNode->appendChild(new \DOMAttr('id', $id));

        $source = $dom->createElement('source');
        $source->appendChild($dom->createCDATASection($key));
        $translationNode->appendChild($source);

        $target = $dom->createElement('target');
        $target->appendChild($dom->createCDATASection($value));
        $translationNode->appendChild($target);

        return $translationNode;
    }
}
