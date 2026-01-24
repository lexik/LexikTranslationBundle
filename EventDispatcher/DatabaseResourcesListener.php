<?php

namespace Lexik\Bundle\TranslationBundle\EventDispatcher;

use Lexik\Bundle\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Lexik\Bundle\TranslationBundle\Translation\Loader\DatabaseLoader;
use Lexik\Bundle\TranslationBundle\Translation\TranslatorDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Event listener to add database translation resources to the translator.
 *
 * This listener ensures that database translations are loaded before the translator
 * is used. It works with Symfony 8 where Translator is final by using the
 * DatabaseLoader directly.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseResourcesListener implements EventSubscriberInterface
{
    private bool $resourcesAdded = false;

    public function __construct(
        #[Autowire(service: 'translator')]
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'Lexik\Bundle\TranslationBundle\Translation\Loader')]
        private readonly DatabaseLoader $databaseLoader,
        #[Autowire([
            'resources_type' => '%lexik_translation.resources_type%'
        ])]
        private readonly array $options
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['addDatabaseResources', 10],
        ];
    }

    public function addDatabaseResources(RequestEvent $event): void
    {
        if ($this->resourcesAdded) {
            return;
        }

        $resourcesType = $this->options['resources_type'] ?? 'all';
        if ('all' !== $resourcesType && 'database' !== $resourcesType) {
            return;
        }

        // Get database resources via event
        $eventDispatcher = $event->getKernel()->getContainer()->get('event_dispatcher');
        $getResourcesEvent = new GetDatabaseResourcesEvent();
        $eventDispatcher->dispatch($getResourcesEvent);

        $resources = $getResourcesEvent->getResources();

        // Add resources to translator if it's our decorator
        if ($this->translator instanceof TranslatorDecorator) {
            $this->translator->addDatabaseResources();
        } elseif (method_exists($this->translator, 'addResource')) {
            // Fallback for direct translator access
            foreach ($resources as $resource) {
                $locale = $resource['locale'];
                $domain = $resource['domain'] ?? 'messages';
                $this->translator->addResource('database', 'DB', $locale, $domain);
            }
        }
        // Note: If neither works, the DatabaseLoader will still be called
        // automatically when translations are requested for a locale/domain

        $this->resourcesAdded = true;
    }
}
