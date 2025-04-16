<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

use Doctrine\Common\Collections\Collection;
/**
 * TransUnit manager interface.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface TransUnitInterface
{
    /**
     * @return TranslationInterface[]
     */
    public function getTranslations(): array|Collection;

    /**
     * @param string $locale
     *
     * @return bool
     */
    public function hasTranslation(bool $locale): bool;

    /**
     * @param string $locale
     *
     * @return TranslationInterface
     */
    public function getTranslation(string $locale): TranslationInterface;

    /**
     * @param string $key
     */
    public function setKey(string $key): void;

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void;
}
