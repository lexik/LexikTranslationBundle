<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Exporter;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\XliffExporter;

/**
 * XliffExporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class XliffExporterTest extends \PHPUnit_Framework_TestCase
{
    private $outFileName = '/file.en.out';

    public function tearDown()
    {
        $outFile = __DIR__ . $this->outFileName;

        if (file_exists(__DIR__ . $this->outFileName)) {
            unlink(__DIR__ . $this->outFileName);
        }
    }

    /**
     * @group exporter
     */
    public function testExportWithoutValues()
    {
        $outFile = __DIR__ . $this->outFileName;

        $exporter = new XliffExporter();

        // export empty array
        $exporter->export($outFile, array());
        $expectedContent = <<<C
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="file.ext" target-language="en">
    <body/>
  </file>
</xliff>

C;
        $this->assertXmlStringEqualsXmlFile($outFile, $expectedContent);
    }

    /**
     * @group exporter
     */
    public function testExportWithValues()
    {
        $outFile = __DIR__ . $this->outFileName;

        $exporter = new XliffExporter();

        // export array with values
        $exporter->export($outFile, array(
            'key.a' => 'aaa',
            'key.b' => 'bbb',
            'key.c' => 'ccc',
        ));
        $expectedContent = <<<C
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="file.ext" target-language="en">
    <body>
      <trans-unit id="1" approved="yes">
        <source><![CDATA[key.a]]></source>
        <target><![CDATA[aaa]]></target>
      </trans-unit>
      <trans-unit id="2" approved="yes">
        <source><![CDATA[key.b]]></source>
        <target><![CDATA[bbb]]></target>
      </trans-unit>
      <trans-unit id="3" approved="yes">
        <source><![CDATA[key.c]]></source>
        <target><![CDATA[ccc]]></target>
      </trans-unit>
    </body>
  </file>
</xliff>

C;
        $this->assertXmlStringEqualsXmlFile($outFile, $expectedContent);
    }
}
