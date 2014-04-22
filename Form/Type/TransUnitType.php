<?php

namespace Lexik\Bundle\TranslationBundle\Form\Type;

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
        $builder->add('key', 'text', array(
            'label' => 'translations.key',
        ));
        $builder->add('domain', 'choice', array(
            'label'   => 'translations.domain',
            'choices' => array_combine($options['domains'], $options['domains']),
        ));
        $builder->add('translations', 'collection', array(
            'type'     => 'lxk_translation',
            'label'    => 'translations.page_title',
            'required' => false,
            'options'  => array(
                'data_class' => $options['translation_class'],
            )
        ));
        $builder->add('save', 'submit', array(
            'label' => 'translations.save',
        ));
        $builder->add('save_add', 'submit', array(
            'label' => 'translations.save_add',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => null,
            'domains'            => array('messages'),
            'translation_class'  => null,
            'translation_domain' => 'LexikTranslationBundle'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'lxk_trans_unit';
    }
}
