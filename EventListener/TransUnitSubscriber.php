<?php

namespace Lexik\Bundle\TranslationBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Lexik\Bundle\TranslationBundle\Entity\TransUnit;

/**
 * Listen events on TransUnit entity.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitSubscriber implements EventSubscriber
{
    /**
     * @var boolean
     */
    private $forceLowerCase;

    /**
     * Construct.
     *
     * @param boolean $forceLowerCase
     */
    public function __construct($forceLowerCase)
    {
        $this->forceLowerCase = $forceLowerCase;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common.EventSubscriber::getSubscribedEvents()
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::preUpdate,
        );
    }

    /**
     * Listen prePersist event.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->convertKeyToLowerCase($args->getEntity());
    }

    /**
     * Listen preUpdate event.
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->convertKeyToLowerCase($args->getEntity());
    }

    /**
     * Convert trans unit key to lower case.
     *
     * @param TransUnit $entity
     */
    protected function convertKeyToLowerCase($entity)
    {
        if ($entity instanceof TransUnit && $this->forceLowerCase) {
            $entity->setKey(mb_strtolower($entity->getKey(), 'UTF-8'));
        }
    }
}