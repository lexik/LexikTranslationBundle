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
    public function export(string $file, array $translations): bool
    {
        $dom = $this->createXmlDocument();

        // Determine whether file format contains target language
        $fileInfo = explode('.', $file);
        $targetLanguage = $fileInfo[count($fileInfo) - 2];
        if (count($fileInfo) > 2 && is_string($targetLanguage) && strlen($targetLanguage) == 2) {
            $bodyNode = $this->addRootNodes($dom, $targetLanguage);
        } else {
            $bodyNode = $this->addRootNodes($dom);
        }

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
    public function support(string $format): bool
    {
        return ('xlf' === $format || 'xliff' === $format);
    }

    /**
     * Create a new xml document.
     *
     * @return \DOMDocument
     */
    protected function createXmlDocument(): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        return $dom;
    }

    /**
     * Add root nodes to a document.
     * @throws \DOMException
     */
    protected function addRootNodes(\DOMDocument $dom, ?string $targetLanguage = null): \DOMElement
    {
        $xliff = $dom->appendChild($dom->createElement('xliff'));
        $xliff->appendChild(new \DOMAttr('xmlns', 'urn:oasis:names:tc:xliff:document:1.2'));
        $xliff->appendChild(new \DOMAttr('version', '1.2'));

        $fileNode = $xliff->appendChild($dom->createElement('file'));
        $fileNode->appendChild(new \DOMAttr('source-language', 'en'));
        $fileNode->appendChild(new \DOMAttr('datatype', 'plaintext'));
        $fileNode->appendChild(new \DOMAttr('original', 'file.ext'));

        if (!is_null($targetLanguage)) {
            $fileNode->appendChild(new \DOMAttr('target-language', $targetLanguage));
        }

        $body = $dom->createElement('body');
        $fileNode->appendChild($body);

        return $body;
    }

    /**
     * Create a new trans-unit node.
     *
     * @throws \DOMException
     */
    protected function createTranslationNode(\DOMDocument $dom, int $id, string $key, string $value): \DOMElement
    {
        $translationNode = $dom->createElement('trans-unit');
        $translationNode->appendChild(new \DOMAttr('id', (string) $id));

        /**
         * @see http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#approved
         */
        if ($value !== '') {
            $translationNode->appendChild(new \DOMAttr('approved', 'yes'));
        }

        $source = $dom->createElement('source');
        $source->appendChild($dom->createCDATASection($key));
        $translationNode->appendChild($source);

        $target = $dom->createElement('target');
        $target->appendChild($dom->createCDATASection($value));
        $translationNode->appendChild($target);

        return $translationNode;
    }
}
