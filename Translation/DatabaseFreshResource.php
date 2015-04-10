<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Class used to represent a translation resource coming from the database.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseFreshResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $domain;

    /**
     *
     * @param string $locale
     * @param string $domain
     */
    public function __construct($locale, $domain)
    {
        $this->locale = $locale;
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
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
