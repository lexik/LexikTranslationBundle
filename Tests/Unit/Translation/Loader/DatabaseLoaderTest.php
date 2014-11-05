<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation\Loader;

use Lexik\Bundle\TranslationBundle\Translation\Loader\DatabaseLoader;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * DatabaseLoader tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseLoaderTest extends BaseUnitTestCase
{
    /**
     * @group loader
     */
    public function testLoad()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);
        
        $appKernel = $this->getMock('AppKernel', array('getFolderClient'));
        $appKernel->expects($this->any())
		          ->method('getFolderClient')
		          ->will($this->returnValue('Custom'));

        $loader = new DatabaseLoader($this->getORMStorage($em), $appKernel);

        $catalogue = $loader->load(null, 'it');
        $this->assertInstanceOf('Symfony\Component\Translation\MessageCatalogue', $catalogue);
        $this->assertEquals(array(), $catalogue->all());
        $this->assertEquals('it', $catalogue->getLocale());

        $catalogue = $loader->load(null, 'fr');
        $expectedTranslations = array(
            'messages' => array(
                'journey.form.tab_title' => 'Itinéraire Custom',
            	'schedule.form.tab_title' => 'Horaires'
            )
        );
        $this->assertInstanceOf('Symfony\Component\Translation\MessageCatalogue', $catalogue);
        $this->assertEquals('fr', $catalogue->getLocale());
        $this->assertEquals($expectedTranslations, $catalogue->all());
    }
}