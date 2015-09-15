<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Exporter;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\JsonExporter;

/**
 * JsonExporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class JsonExporterTest extends \PHPUnit_Framework_TestCase
{
    private $outFileName = '/file.out';

    public function tearDown()
    {
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

        $exporter = new JsonExporter();

        // export empty array
        $exporter->export($outFile, array());
        $expectedContent = <<<C
[]
C;
        $this->assertJsonStringEqualsJsonFile($outFile, $expectedContent);

        // export array with values
        $exporter->export($outFile, array(
            'key.a' => 'aaa',
            'key.b' => 'bbb',
            'key.c' => 'ccc',
        ));
        $expectedContent = <<<EOL
{
    "key.a": "aaa",
    "key.b": "bbb",
    "key.c": "ccc"
}
EOL;
        $this->assertJsonStringEqualsJsonFile($outFile, $expectedContent);
    }
}
