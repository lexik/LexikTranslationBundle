<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Exporter;

use Lexik\Bundle\TranslationBundle\Translation\Exporter\YamlExporter;

/**
 * YamlExporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class YamlExporterTest extends \PHPUnit_Framework_TestCase
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

        $exporter = new YamlExporter();

        // export empty array
        $exporter->export($outFile, array());
        $expectedContent = '{  }';
        $this->assertEquals($expectedContent, file_get_contents($outFile));

        // export array with values
        $exporter->export($outFile, array(
            'key.a' => 'aaa',
            'key.b' => 'bbb',
            'key.c' => 'ccc',
        ));
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

        $result=$exporter->createMultiArray(array('foo.bar.baz'=>'foobar'));
        $expected=array('foo'=>array('bar'=>array('baz'=>'foobar')));
        $this->assertEquals($expected, $result);

        $result=$exporter->createMultiArray(array(
                'foo.bar.baz'=>'foobar',
                'foo.foobaz'=>'bazbar',
            ));
        $expected=array('foo'=>array(
            'foobaz'=>'bazbar',
            'bar'=>array('baz'=>'foobar'),
        ));
        $this->assertEquals($expected, $result);
    }

    /**
     * @group exporter
     */
    public function testflattenArray()
    {
        $exporter = new TmpExporter();

        $result=$exporter->flattenArray(array('foo'=>array('bar'=>array('baz'=>'foobar'))));
        $expected=array('foo.bar.baz'=>'foobar');
        $this->assertEquals($expected, $result);

        $result=$exporter->flattenArray(
            array('bundle'=>
                array('foo'=>
                    array(
                        'foobaz'=>'bazbar',
                        'bar'=>
                            array(
                                'baz0'=>'foobar',
                                'baz1'=>'foobaz',
                            ),
                    )
                )
            )
        );
        $expected=array('bundle.foo'=>
            array(
                'foobaz'=>'bazbar',
                'bar'=>
                    array(
                        'baz0'=>'foobar',
                        'baz1'=>'foobaz',
                    ),
            )
        );
        $this->assertEquals($expected, $result);

        $result=$exporter->flattenArray(
            array('bundle'=>
                array('foo'=>
                    array(
                        'foobaz'=>'bazbar',
                        'bar'=>array('baz'=>'foobar'),
                    )
                )
            )
        );
        $expected=array('bundle.foo'=>
            array(
                'foobaz'=>'bazbar',
                'bar'=>array('baz'=>'foobar'),
            )
        );
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

    public function flattenArray($array, $prefix='')
    {
        return parent::flattenArray($array, $prefix);
    }
}