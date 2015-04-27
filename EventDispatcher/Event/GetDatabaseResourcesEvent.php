<?php

namespace Lexik\Bundle\TranslationBundle\EventDispatcher\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class GetDatabaseResourcesEvent extends Event
{
    /**
     * @var array
     */
    private $resources;

    /**
     * Set database resources.
     *
     * @param $resources
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * Get database resources.
     *
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }
}
