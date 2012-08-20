<?php

namespace Lexik\Bundle\TranslationBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * TransUnit form type.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'data_class'        => null,
            'domains'           => array('messages'),
            'translation_class' => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'trans_unit';
    }
}