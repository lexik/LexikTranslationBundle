<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Translation;

use Lexik\Bundle\TranslationBundle\Translation\DatabaseFreshResource;

/**
 * DatabaseFreshResource tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseFreshResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group translator
     */
    public function testIsFresh()
    {
        $resource = new DatabaseFreshResource('fr', 'messages');

        $date = new \DateTime('now');
        $this->assertTrue($resource->isFresh($date->format('U')));

        $date->modify('+1 day');
        $this->assertTrue($resource->isFresh($date->format('U')));

        $date->modify('+10 days');
        $this->assertTrue($resource->isFresh($date->format('U')));
    }

    /**
     * @group translator
     */
    public function testGetResource()
    {
        $resource = new DatabaseFreshResource('fr', 'messages');
        $this->assertEquals('fr:messages', $resource->getResource());

        $resource = new DatabaseFreshResource('fr', 'blablabla');
        $this->assertEquals('fr:blablabla', $resource->getResource());

        $resource = new DatabaseFreshResource('en', 'messages');
        $this->assertEquals('en:messages', $resource->getResource());
        $this->assertEquals('en:messages', $resource->__toString());
    }
}