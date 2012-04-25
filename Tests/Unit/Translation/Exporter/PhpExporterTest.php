<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Exporter;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\PhpExporter;

/**
 * PhpExporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class PhpExporterTest extends \PHPUnit_Framework_TestCase
{
    private $outFileName = '/file.out';

    public function tearDown()
    {
        $outFile = __DIR__.$this->outFileName;

        if (file_exists(__DIR__.$this->outFileName)) {
            unlink(__DIR__.$this->outFileName);
        }
    }

    /**
     * @group exporter
     */
    public function testExport()
    {
        $outFile = __DIR__.$this->outFileName;

        $exporter = new PhpExporter();

        // export empty array
        $exporter->export($outFile, array());
        $expectedContent = <<<C
<?php
return array (
);
C;
        $this->assertEquals($expectedContent, file_get_contents($outFile));

        // export array with values
        $exporter->export($outFile, array(
            'key.a' => 'aaa',
            'key.b' => 'bbb',
            'key.c' => 'ccc',
        ));
        $expectedContent = <<<C
<?php
return array (
  'key.a' => 'aaa',
  'key.b' => 'bbb',
  'key.c' => 'ccc',
);
C;
        $this->assertEquals($expectedContent, file_get_contents($outFile));
    }
}