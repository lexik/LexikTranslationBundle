<?php

namespace Lexik\Bundle\TranslationBundle\Util\Profiler;

use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TokenFinder
{
    public function __construct(
        private readonly Profiler $profiler,
        private readonly int $defaultLimit,
    ) {
    }

    /**
     * @param string $ip
     * @param string $url
     * @param int    $limit
     * @param string $method
     * @param string $start
     * @param string $end
     * @return array
     */
    public function find($ip = null, $url = null, $limit = null, $method = null, $start = null, $end = null)
    {
        $limit = $limit ?: $this->defaultLimit;

        return $this->profiler->find($ip, $url, $limit, $method, $start, $end);
    }
}
