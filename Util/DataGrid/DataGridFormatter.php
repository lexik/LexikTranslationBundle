<?php

namespace Lexik\Bundle\TranslationBundle\Util\DataGrid;

use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridFormatter
{
    public function __construct(
        protected LocaleManagerInterface $localeManager,
        protected string $storage
    ) {
    }

    /**
     * Returns a JSON response with formatted data.
     */
    public function createListResponse(array $transUnits, int $total): JsonResponse
    {
        return new JsonResponse(['translations' => $this->format($transUnits), 'total'        => $total]);
    }

    /**
     * Returns a JSON response with formatted data.
     */
    public function createSingleResponse(mixed $transUnit): JsonResponse
    {
        return new JsonResponse($this->formatOne($transUnit));
    }

    /**
     * Format the translations list.
     */
    protected function format(array $transUnits): array
    {
        $formatted = [];

        foreach ($transUnits as $transUnit) {
            $formatted[] = $this->formatOne($transUnit);
        }

        return $formatted;
    }

    /**
     * Format a single TransUnit.
     */
    protected function formatOne(TransUnitInterface|array $transUnit): array
    {
        if (is_object($transUnit)) {
            $transUnit = $this->toArray($transUnit);
        } elseif (StorageInterface::STORAGE_MONGODB === $this->storage) {
            $transUnit['id'] = $transUnit['_id']->{'$id'};
        }

        $formatted = [
            '_id'     => $transUnit['id'],
            '_domain' => $transUnit['domain'],
            '_key'    => $transUnit['key'],
        ];

        // add locales in the same order as in managed_locales param
        foreach ($this->localeManager->getLocales() as $locale) {
            $formatted[$locale] = '';
        }

        // then fill locales value
        foreach ($transUnit['translations'] as $translation) {
            if (in_array($translation['locale'], $this->localeManager->getLocales(), true)) {
                $formatted[$translation['locale']] = $translation['content'];
            }
        }

        return $formatted;
    }

    /**
     * Convert a trans unit into an array.
     */
    protected function toArray(TransUnitInterface $transUnit): array
    {
        $data = [
            'id'           => $transUnit->getId(),
            'domain'       => $transUnit->getDomain(),
            'key'          => $transUnit->getKey(),
            'translations' => [],
        ];

        foreach ($transUnit->getTranslations() as $translation) {
            $data['translations'][] = ['locale'  => $translation->getLocale(), 'content' => $translation->getContent()];
        }

        return $data;
    }
}
