<?php

namespace Lexik\Bundle\TranslationBundle\Translation\Exporter;

use Symfony\Component\Yaml\Dumper;

/**
 * Export translations to a Yaml file.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class YamlExporter implements ExporterInterface
{
    private $createTree;

    /**
     * @param bool $createTree
     */
    public function __construct($createTree=false)
    {
        $this->createTree = $createTree;
    }

    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        if ($this->createTree) {
            $result=$this->createMultiArray($translations);
            $translations=$this->flattenArray($result);
        }

        $ymlDumper = new Dumper();
        $ymlDumper->setIndentation(2);
        $ymlContent = $ymlDumper->dump($translations,10);

        $bytes = file_put_contents($file, $ymlContent);

        return ($bytes !== false);
    }

    /**
     * Create a multi dimension array.
     *
     * If you got a array like array('foo.bar.baz'=>'foobar') we will create an array like:
     * array('foo'=>array('bar'=>array('baz'=>'foobar')))
     *
     * @param array $translations
     *
     * @return array
     */
    protected function createMultiArray(array $translations)
    {
        $res=array();

        foreach($translations as $keyString=>$value) {
            $keys=explode('.',$keyString);

            //$keyString might be "Hello world."
            $keyLength = count($keys);
            if ($keys[$keyLength-1]=='') {
                unset($keys[$keyLength-1]);
                $keys[$keyLength-2].='.';
            }

            $this->addValueToMultiArray($res, $value, $keys);
        }

        return $res;
    }

	/**
     *
     *
     * @param array $array
     * @param $value
     * @param array $keys
     *
     * @throws \InvalidArgumentException
     */
    private function addValueToMultiArray(array &$array, $value, array $keys)
    {
        $key=array_shift($keys);

        //base case
        if (count($keys)==0) {
            $array[$key]=$value;

            return;
        }

        if (!isset($array[$key])) {
            $array[$key]=array();
        }
        elseif(!is_array($array[$key])){
            //if $array[$key] isset but is not array
            throw new \InvalidArgumentException('Found an leaf, expected a tree');
        }

        $this->addValueToMultiArray($array[$key], $value, $keys);
    }

    /**
     * Make sure we flatten the array in the begnning to make a lower tree
     *
     * @param mixed $array
     * @param string $prefix
     *
     * @return mixed
     */
    protected  function flattenArray($array, $prefix='')
    {
        if (is_array($array)){
            foreach ($array as $key=>$subarray) {
                if (count($array)==1) {
                    return $this->flattenArray($subarray, ($prefix==''?$prefix:$prefix.'.').$key);
                }

                $array[$key]=$this->flattenArray($subarray);
            }
        }

        if ($prefix=='') {
            return $array;
        }

        return array($prefix=>$array);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('yml' == $format);
    }
}
