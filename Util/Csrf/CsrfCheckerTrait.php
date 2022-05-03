<?php

namespace Lexik\Bundle\TranslationBundle\Util\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CsrfChecker.
 */
trait CsrfCheckerTrait
{
    private $requestStack;

    private $tokenManager;

    /**
     * @required
     */
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @required
     */
    public function setTokenManager(?CsrfTokenManager $tokenManager): void
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * @param string $id    The id used when generating the token
     * @param string $query
     */
    protected function checkCsrf($id = 'lexik-translation', $query = '_token')
    {
        if (!$this->tokenManager) {
            return;
        }

        if (!$this->isCsrfTokenValid($id, $this->requestStack->getCurrentRequest()->get($query))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}
