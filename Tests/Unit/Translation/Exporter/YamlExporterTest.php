<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Exporter;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\YamlExporter;
use PHPUnit\Framework\TestCase;

/**
 * YamlExporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class YamlExporterTest extends TestCase
{
    private string $outFileName = '/file.out';

    public function tearDown(): void
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

        $exporter = new YamlExporter();

        // export empty array
        $exporter->export($outFile, []);
        $expectedContent = '{  }';
        $this->assertEquals($expectedContent, trim(file_get_contents($outFile)));

        // export array with values
        $exporter->export($outFile, ['key.a' => 'aaa', 'key.b' => 'bbb', 'key.c' => 'ccc']);
        $expectedContent = <<<C
key.a: aaa
key.b: bbb
key.c: ccc

C;
        $this->assertEquals($expectedContent, file_get_contents($outFile));
    }
    /**
     * @group exporter
     */
    public function testCreateMultiArray()
    {
        $exporter = new TmpExporter();

        $result = $exporter->createMultiArray(['foo.bar.baz' => 'foobar']);
        $expected = ['foo' => ['bar' => ['baz' => 'foobar']]];
        $this->assertEquals($expected, $result);

        $result = $exporter->createMultiArray(['foo.bar.baz' => 'foobar', 'foo.foobaz' => 'bazbar']);
        $expected = ['foo' => ['foobaz' => 'bazbar', 'bar'   => ['baz' => 'foobar']]];
        $this->assertEquals($expected, $result);
    }

    /**
     * @group exporter
     */
    public function testflattenArray()
    {
        $exporter = new TmpExporter();

        $result = $exporter->flattenArray(['foo' => ['bar' => ['baz' => 'foobar']]]);
        $expected = ['foo.bar.baz' => 'foobar'];
        $this->assertEquals($expected, $result);

        $result = $exporter->flattenArray(
            ['bundle' => ['foo' => ['foobaz' => 'bazbar', 'bar'   => ['baz0' => 'foobar', 'baz1' => 'foobaz']]]]
        );
        $expected = ['bundle.foo' => ['foobaz' => 'bazbar', 'bar'   => ['baz0' => 'foobar', 'baz1' => 'foobaz']]];
        $this->assertEquals($expected, $result);

        $result = $exporter->flattenArray(
            ['bundle' => ['foo' => ['foobaz' => 'bazbar', 'bar'   => ['baz' => 'foobar']]]]
        );
        $expected = ['bundle.foo' => ['foobaz' => 'bazbar', 'bar'   => ['baz' => 'foobar']]];
        $this->assertEquals($expected, $result);
    }
}

/**
 * Class TmpExporter
 *
 * @author Tobias Nyholm
 *
 * Use this class to exploit protected functions
 */
class TmpExporter extends YamlExporter
{
    public function createMultiArray(array $translations)
    {
        return parent::createMultiArray($translations);
    }

    public function flattenArray($array, $prefix = '')
    {
        return parent::flattenArray($array, $prefix);
    }
}
