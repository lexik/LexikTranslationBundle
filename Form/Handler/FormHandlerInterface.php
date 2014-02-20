<?php

namespace Lexik\Bundle\TranslationBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface FormHandlerInterface
{
    /**
     * Create an element to be used as form data.
     *
     * @return mixed
     */
    public function createFormData();

    /**
     * Returns an array with options to pass to the form.
     *
     * @return array
     */
    public function getFormOptions();

    /**
     * Process the form and returns true if the form is valid.
     *
     * @param FormInterface $form
     * @param Request $request
     * @return boolean
     */
    public function process(FormInterface $form, Request $request);
}
