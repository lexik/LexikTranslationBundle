<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

use Lexik\Bundle\TranslationBundle\Model\Translation as BaseTranslation;

/**
 * @MongoDB\Document
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Translation extends BaseTranslation
{
}