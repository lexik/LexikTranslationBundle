<?php

namespace Lexik\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('locale', HiddenType::class);
        $builder->add('content', TextareaType::class, ['required' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['label'] = $form['locale']->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class'         => null, 'translation_domain' => 'LexikTranslationBundle']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'lxk_translation';
    }
}
