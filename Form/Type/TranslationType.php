<?php

namespace Lexik\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Translation form type.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('locale', 'hidden');
        $builder->add('content', 'textarea', array(
            'required' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label'] = $form['locale']->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => null,
            'translation_domain' => 'LexikTranslationBundle',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'lxk_translation';
    }
}
