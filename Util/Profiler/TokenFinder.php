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
     * @param Profiler $profiler
     */
    public function __construct(Profiler $profiler)
    {
        $this->profiler = $profiler;
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
        $limit = $limit ?: 10;
        $method = $method ?: 'GET';

        return $this->profiler->find($ip, $url, $limit, $method, $start, $end);
    }
}
