<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

use Lexik\Bundle\TranslationBundle\Model\TransUnit as BaseTransUnit;

/**
 * @MongoDB\Document
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends BaseTransUnit
{
}