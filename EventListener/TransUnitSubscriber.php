<?php

namespace Lexik\Bundle\TranslationBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Lexik\Bundle\TranslationBundle\Model\TransUnit;

/**
 * Listen events on TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitSubscriber implements EventSubscriber
{
    /**
     * @var string
     */
    private $storage;

    /**
     * @var boolean
     */
    private $forceLowerCase;

    /**
     * Construct.
     *
     * @param string $storage
     * @param boolean $forceLowerCase
     */
    public function __construct($storage, $forceLowerCase)
    {
        $this->storage = $storage;
        $this->forceLowerCase = $forceLowerCase;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common.EventSubscriber::getSubscribedEvents()
     */
    public function getSubscribedEvents()
    {
        $events = array();

        if ($this->storage == 'orm') {
            $events = array(
                \Doctrine\ORM\Events::prePersist,
                \Doctrine\ORM\Events::preUpdate,
            );
        }
        else if ($this->storage == 'mongodb') {
            $events = array(
                \Doctrine\ODM\MongoDB\Events::prePersist,
                \Doctrine\ODM\MongoDB\Events::preUpdate,
            );
        }

        return $events;
    }

    /**
     * Listen prePersist event.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(EventArgs $args)
    {
        $this->convertKeyToLowerCase($args);
    }

    /**
     * Listen preUpdate event.
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(EventArgs $args)
    {
        $this->convertKeyToLowerCase($args);
    }

    /**
     * Convert trans unit key to lower case.
     *
     * @param TransUnit $object
     */
    protected function convertKeyToLowerCase(EventArgs $args)
    {
        $object = null;

        if ($this->storage == 'orm') {
            $object = $args->getEntity();
        }
        else if ($this->storage == 'mongodb') {
            $object = $args->getDocument();
        }

        if ($object instanceof TransUnit && $this->forceLowerCase) {
            $object->setKey(mb_strtolower($object->getKey(), 'UTF-8'));
        }
    }
}