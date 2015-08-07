<?php

namespace Lexik\Bundle\TranslationBundle\Util\Profiler;

use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TokenFinder
{
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var int
     */
    private $defaultLimit;

    /**
     * @param Profiler $profiler
     * @param int      $defaultLimit
     */
    public function __construct(Profiler $profiler, $defaultLimit)
    {
        $this->profiler = $profiler;
        $this->defaultLimit = $defaultLimit;
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
