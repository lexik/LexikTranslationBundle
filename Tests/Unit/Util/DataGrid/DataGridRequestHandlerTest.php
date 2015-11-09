<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Util\DataGrid;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManager;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridRequestHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridRequestHandlerTest extends BaseUnitTestCase
{
    /**
     * @group util
     */
    public function testFilterTokenTranslationsEmpty()
    {
        $method = $this->getReflectionMethod('filterTokenTranslations');
        $handler = $this->getDataGridRequestHandler();

        $result = $method->invokeArgs($handler, array(
            array(),
            0,
            array('rows' => 20, 'page' => 1)
        ));

        $this->assertEquals(array(array(), 0), $result);
    }

    /**
     * @group util
     */
    public function testFilterTokenTranslationsPage()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);

        $ormStorage = $this->getORMStorage($em);

        $method = $this->getReflectionMethod('filterTokenTranslations');
        $handler = $this->getDataGridRequestHandler();

        $transUnits = array();
        $transUnits[] = $ormStorage->getTransUnitByKeyAndDomain('key.say_hello', 'superTranslations');
        $transUnits[] = $ormStorage->getTransUnitByKeyAndDomain('key.say_goodbye', 'messages');
        $transUnits[] = $ormStorage->getTransUnitByKeyAndDomain('key.say_wtf', 'messages');

        // 20 rows -> all in one page
        list($result, $count) = $method->invokeArgs($handler, array(
            $transUnits,
            count($transUnits),
            array('rows' => 20, 'page' => 1)
        ));

        $this->assertEquals(3, $count);
        $this->assertEquals(3, count($result));
        $this->assertEquals('key.say_hello', $result[0]->getKey());
        $this->assertEquals('key.say_goodbye', $result[1]->getKey());
        $this->assertEquals('key.say_wtf', $result[2]->getKey());

        // only 2 rows -> 2 pages
        list($result, $count) = $method->invokeArgs($handler, array(
            $transUnits,
            count($transUnits),
            array('rows' => 2, 'page' => 2)
        ));

        $this->assertEquals(3, $count);
        $this->assertEquals(1, count($result));
        $this->assertEquals('key.say_wtf', $result[0]->getKey());
    }

    /**
     * @group util
     */
    public function testFilterTokenTranslationsFilters()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);

        $ormStorage = $this->getORMStorage($em);

        $method = $this->getReflectionMethod('filterTokenTranslations');
        $handler = $this->getDataGridRequestHandler();

        $transUnits = array();
        $transUnits[] = $ormStorage->getTransUnitByKeyAndDomain('key.say_hello', 'superTranslations');
        $transUnits[] = $ormStorage->getTransUnitByKeyAndDomain('key.say_goodbye', 'messages');
        $transUnits[] = $ormStorage->getTransUnitByKeyAndDomain('key.say_wtf', 'messages');

        // filter by domain
        list($result, $count) = $method->invokeArgs($handler, array(
            $transUnits,
            count($transUnits),
            array('rows' => 20, 'page' => 1, '_search' => true, 'domain' => 'mess')
        ));

        $this->assertEquals(2, $count);
        $this->assertEquals(2, count($result));
        $this->assertEquals('key.say_goodbye', $result[0]->getKey());
        $this->assertEquals('key.say_wtf', $result[1]->getKey());

        // filter by domain and locale en
        list($result, $count) = $method->invokeArgs($handler, array(
            $transUnits,
            count($transUnits),
            array('rows' => 20, 'page' => 1, '_search' => true, 'domain' => 'mess', 'en' => 'the fu')
        ));

        $this->assertEquals(1, $count);
        $this->assertEquals(1, count($result));
        $this->assertEquals('key.say_wtf', $result[0]->getKey());
    }

    /**
     * @group util
     */
    public function testFixParameters()
    {
        $method = $this->getReflectionMethod('fixParameters');
        $handler = $this->getDataGridRequestHandler();

        // no params
        $result = $method->invokeArgs($handler, array(
            array(),
        ));

        $this->assertEquals(array(), $result);

        // page and rows
        $result = $method->invokeArgs($handler, array(
            array('page' => 1, 'rows' => 10),
        ));

        $this->assertEquals(array('page' => 1, 'rows' => 10), $result);

        // page and rows + filters
        $result = $method->invokeArgs($handler, array(
            array('page' => 1, 'rows' => 10, '_search' => true, '_domain' => 'super', '_en' => 'man'),
        ));

        $this->assertEquals(
            array('page' => 1, 'rows' => 10, '_search' => true, 'domain' => 'super', 'en' => 'man'),
            $result
        );
    }

    /**
     * @return \ReflectionMethod
     */
    private function getReflectionMethod($name)
    {
        $class = new \ReflectionClass('Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridRequestHandler');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @return DataGridRequestHandler
     */
    private function getDataGridRequestHandler()
    {
        $transUnitManagerMock = $this->getMockBuilder('Lexik\Bundle\TranslationBundle\Manager\TransUnitManager')
            ->disableOriginalConstructor()
            ->getMock();

        $storageMock = $this->getMockBuilder('Lexik\Bundle\TranslationBundle\Storage\StorageInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $localeManager = new LocaleManager(array());

        return new DataGridRequestHandler($transUnitManagerMock, $storageMock, $localeManager);
    }
}
