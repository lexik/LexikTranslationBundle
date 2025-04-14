<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

/**
 * Class used to represent a translation resource coming from the database.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseFreshResource implements SelfCheckingResourceInterface, \Stringable
{

    public function __construct(
        private readonly string $locale,
        private readonly string $domain,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) $this->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp): bool
    {
        return true; // Consider a resource comming from the database is always fresh
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return sprintf('%s:%s', $this->locale, $this->domain);
    }
}
