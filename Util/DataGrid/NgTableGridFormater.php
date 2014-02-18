<?php

namespace Lexik\Bundle\TranslationBundle\Util\DataGrid;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class NgTableGridFormater
{
    /**
     * Managed locales.
     *
     * @var array
     */
    protected $locales;

    /**
     * Constructor.
     *
     * @param array $locales
     */
    public function __construct(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * Returns a JSON response with formatted data.
     *
     * @param array   $transUnits
     * @param integer $total
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createResponse($transUnits, $total)
    {
        return new JsonResponse(array(
            'translations' => $this->format($transUnits),
            'total'        => $total,
        ));
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
            $formatted[$transUnit['id']] = array(
                'id'     => $transUnit['id'],
                'domain' => $transUnit['domain'],
                'key'    => $transUnit['key'],
            );

            // add locales in the same order as in managed_locales param
            foreach ($this->locales as $locale) {
                $formatted[$transUnit['id']][$locale] = null;
            }

            // then fill locales value
            foreach ($transUnit['translations'] as $translation) {
                $formatted[$transUnit['id']][$translation['locale']] = $translation['content'];
            }
        }

        return $formatted;
    }
}
