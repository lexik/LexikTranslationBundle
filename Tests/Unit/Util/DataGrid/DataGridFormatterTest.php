<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Util\DataGrid;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManager;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridFormatter;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridFormatterTest extends BaseUnitTestCase
{
    /**
     * @group util
     */
    public function testCreateListResponse()
    {
        $datas = [['id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'au revoir'], ['locale' => 'en', 'content' => 'good bye']]], ['id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'translations' => [['locale' => 'fr', 'content' => 'salut'], ['locale' => 'de', 'content' => 'heil']]], ['id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'translations' => [['locale' => 'fr', 'content' => 'c\'est quoi ce bordel !?!'], ['locale' => 'xx', 'content' => 'xxx xxx xxx']]]];
        $total = 3;

        $expected = ['translations' => [['_id'     => 2, '_domain' => 'messages', '_key'    => 'key.say_goodbye', 'de'      => '', 'en'      => 'good bye', 'fr'      => 'au revoir'], ['_id'     => 1, '_domain' => 'superTranslations', '_key'    => 'key.say_hello', 'de'      => 'heil', 'en'      => '', 'fr'      => 'salut'], ['_id'     => 3, '_domain' => 'messages', '_key'    => 'key.say_wtf', 'de'      => '', 'en'      => '', 'fr'      => 'c\'est quoi ce bordel !?!']], 'total' => 3];

        $formatter = new DataGridFormatter(new LocaleManager(['de', 'en', 'fr']), StorageInterface::STORAGE_ORM);
        $this->assertEquals(json_encode($expected, JSON_HEX_APOS), $formatter->createListResponse($datas, $total)->getContent());
    }
}
