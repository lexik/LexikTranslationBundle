<?php

namespace Lexik\Bundle\TranslationBundle\Util\Csrf;

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
    protected function checkCsrf($id = 'lexik-translation', $query = '_token')
    {
        if (!$this->has('security.csrf.token_manager')) {
            return;
        }

        $request = $this->get('request_stack')->getCurrentRequest();

        if (!$this->isCsrfTokenValid($id, $request->get($query))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}