<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

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
    public function getTranslations(): array;

    /**
     * @param string $locale
     *
     * @return bool
     */
    public function hasTranslation($locale): bool;

    /**
     * @param string $locale
     *
     * @return TranslationInterface
     */
    public function getTranslation($locale): TranslationInterface;

    /**
     * @param string $key
     */
    public function setKey($key);

    /**
     * @param string $domain
     */
    public function setDomain($domain);
}
