<?php

namespace Lexik\Bundle\TranslationBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * TransUnit form type.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitType extends AbstractType
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('key');
        $builder->add('domain', 'choice', array(
            'choices' => array_combine($options['domains'], $options['domains']),
        ));
        $builder->add('translations', 'collection', array(
            'type' => new TranslationType(),
            'required' => false,
            'options' => array(
                'data_class' => $options['translation_class'],
            )
        ));
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::getDefaultOptions()
     */
    public function getDefaultOptions(array $options)
    {
        $defaults = array(
            'data_class'        => null,
            'domains'           => array('messages'),
            'translation_class' => null,
        );

        return array_merge($defaults, $options);
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'trans_unit';
    }
}