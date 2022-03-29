<?php

namespace Lexik\Bundle\TranslationBundle\Util\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Class CsrfChecker.
 */
trait CsrfCheckerTrait
{
    /**
     * Checks the validity of a CSRF token.
     *
     * @param string $id    The id used when generating the token
     * @param string $query
     */
    protected function checkCsrf(Request $request, ?CsrfTokenManager $tokenManager, $id = 'lexik-translation', $query = '_token')
    {
        if (!tokenManager) {
            return;
        }

        if (!$this->isCsrfTokenValid($id, $request->get($query))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}
