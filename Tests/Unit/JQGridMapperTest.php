<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit;

use Lexik\Bundle\TranslationBundle\Util\JQGrid\Mapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit test for JQGridMapper class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class JQGridMapperTest extends BaseUnitTestCase
{
    /**
     * @group util
     */
    public function testGenerate()
    {
        $datas = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'de', 'content' => 'heil'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'),
            )),
        );
        $total = 3;
        $request = new Request(array('page' => 1, 'rows' => 20));
        $mapper = new Mapper($request, $datas, $total);
        $expected = array(
            'page' => 1,
            'records' => 3,
            'total' => 1,
            'rows' => array(
              array(
                  'id' => 2,
                  'cell' => array( 2, "messages",  "key.say_goodbye", "", "au revoir"),
              ),
              array(
                  'id' => 1,
                  'cell' => array(1, "superTranslations", "key.say_hello", "heil", "salut"),
              ),
              array(
                  'id' => 3,
                  'cell' => array(3, "messages", "key.say_wtf", "", "c'est quoi ce bordel !?!"),
              ),
            ),
        );
        $this->assertSame($expected, $mapper->generate(array('de', 'fr'), false));


        $datas = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => array(
                array('locale' => 'fr', 'content' => 'au revoir'),
            )),
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => array(
                array('locale' => 'de', 'content' => 'heil'),
                array('locale' => 'fr', 'content' => 'salut'),
            )),
        );
        $total = 3;
        $request = new Request(array('page' => 1, 'rows' => 2));
        $mapper = new Mapper($request, $datas, $total);
        $expected = array(
            'page' => 1,
            'records' => 3,
            'total' => 2,
            'rows' => array(
                array(
                    'id' => 2,
                    'cell' => array( 2, "messages",  "key.say_goodbye", "", "au revoir"),
                ),
                array(
                    'id' => 1,
                    'cell' => array(1, "superTranslations", "key.say_hello", "heil", "salut"),
                ),
            ),
        );
        $this->assertSame($expected, $mapper->generate(array('de', 'fr'), false));
        $this->assertSame(json_encode($expected), $mapper->generate(array('de', 'fr'), true));
    }

}