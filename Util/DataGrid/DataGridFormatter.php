<?php

namespace Lexik\Bundle\TranslationBundle\Util\DataGrid;

use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridFormatter
{
    /**
     * Managed locales.
     *
     * @var array
     */
    protected $locales;

    /**
     * Storage type
     *
     * @var string
     */
    protected $storage;

    /**
     * Constructor.
     *
     * @param array  $locales
     * @param string $storage
     */
    public function __construct(array $locales, $storage)
    {
        $this->locales = $locales;
        $this->storage = $storage;
    }

    /**
     * Returns a JSON response with formatted data.
     *
     * @param array   $transUnits
     * @param integer $total
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createListResponse($transUnits, $total)
    {
        return new JsonResponse(array(
            'translations' => $this->format($transUnits),
            'total'        => $total,
        ));
    }

    /**
     * Returns a JSON response with formatted data.
     *
     * @param mixed $transUnit
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createSingleResponse($transUnit)
    {
        return new JsonResponse($this->formatOne($transUnit));
    }

    /**
     * Format the tanslations list.
     *
     * @param array $transUnits
     * @return array
     */
    protected function format($transUnits)
    {
        $formatted = array();

        foreach ($transUnits as $transUnit) {
            $formatted[] = $this->formatOne($transUnit);
        }

        return $formatted;
    }

    /**
     * Format a single TransUnit.
     *
     * @param array $transUnit
     * @return array
     */
    protected function formatOne($transUnit)
    {
        if (is_object($transUnit)) {
            $transUnit = $this->toArray($transUnit);
        } elseif (StorageInterface::STORAGE_MONGODB == $this->storage) {
            $transUnit['id'] = $transUnit['_id']->{'$id'};
        }

        $formatted = array(
            '_id'     => $transUnit['id'],
            '_domain' => $transUnit['domain'],
            '_key'    => $transUnit['key'],
        );

        // add locales in the same order as in managed_locales param
        foreach ($this->locales as $locale) {
            $formatted[$locale] = '';
        }

        // then fill locales value
        foreach ($transUnit['translations'] as $translation) {
            if (in_array($translation['locale'], $this->locales)) {
                $formatted[$translation['locale']] = $translation['content'];
            }
        }

        return $formatted;
    }

    /**
     * Convert a trans unit into an array.
     *
     * @param TransUnitInterface $transUnit
     * @return array
     */
    protected function toArray(TransUnitInterface $transUnit)
    {
        $data = array(
            'id'           => $transUnit->getId(),
            'domain'       => $transUnit->getDomain(),
            'key'          => $transUnit->getKey(),
            'translations' => array(),
        );

        foreach ($transUnit->getTranslations() as $translation) {
            $data['translations'][] = array(
                'locale'  => $translation->getLocale(),
                'content' => $translation->getContent(),
            );
        }

        return $data;
    }
}
