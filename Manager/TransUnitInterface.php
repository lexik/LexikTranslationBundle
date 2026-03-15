<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Lexik\Bundle\TranslationBundle\Document\Translation as DocumentTranslation;
use Lexik\Bundle\TranslationBundle\Entity\Translation;

/**
 * TransUnit manager interface.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface TransUnitInterface
{
    public function getId();

    public function addTranslation(DocumentTranslation|Translation $translation): void;

    public function removeTranslation(DocumentTranslation|Translation $translation): void;

    public function getTranslations(): Collection;

    public function hasTranslation(string $locale): bool;

    public function getTranslation(string $locale): ?TranslationInterface;

    public function setKey(string $key): void;

    public function getKey(): string;

    public function setDomain(string $domain): void;

    public function getDomain(): string;
}
