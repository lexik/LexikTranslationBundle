<?php

namespace Lexik\Bundle\TranslationBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Translation form type.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationType extends AbstractType
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('locale', 'hidden');
        $builder->add('content', 'textarea', array(
            'required' => false,
        ));
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildView()
     */
    public function buildView(FormView $view, FormInterface $form)
    {
        $view->set('label', $form['locale']->getData());
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::getDefaultOptions()
     */
    public function getDefaultOptions(array $options)
    {
        $defaults = array(
            'data_class' => null,
        );

        return array_merge($defaults, $options);
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'translation';
    }
}