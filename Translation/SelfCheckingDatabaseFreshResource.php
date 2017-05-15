<?php

namespace Lexik\Bundle\TranslationBundle\Translation;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

/**
 * Class used to represent a translation resource coming from the database.
 *
 * @package Lexik\Bundle\TranslationBundle\Translation
 */
class SelfCheckingDatabaseFreshResource extends DatabaseFreshResource implements SelfCheckingResourceInterface
{
}