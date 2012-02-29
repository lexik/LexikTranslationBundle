<?php

namespace Lexik\Bundle\TranslationBundle\Document;

use Lexik\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel
{
    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.TransUnit::prePersist()
     */
    public function prePersist()
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\TranslationBundle\Model.TransUnit::preUpdate()
     */
    public function preUpdate()
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}