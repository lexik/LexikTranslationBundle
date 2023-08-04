<?php

namespace Lexik\Bundle\TranslationBundle\EventDispatcher;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class GetDatabaseResourcesListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string $storageType,
    ) {
    }

    /**
     * Query the database to get translation resources and set it on the event.
     */
    public function onGetDatabaseResources(GetDatabaseResourcesEvent $event)
    {
        // prevent errors on command such as cache:clear if doctrine schema has not been updated yet
        if (StorageInterface::STORAGE_ORM == $this->storageType && !$this->storage->translationsTablesExist()) {
            $resources = [];
        } else {
            $resources = $this->storage->getTransUnitDomainsByLocale();
        }

        $event->setResources($resources);
    }
}
