<?php

namespace Lexik\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder->add('key', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['label' => 'translations.key']);
        $builder->add('domain', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, ['label'   => 'translations.domain', 'choices' => array_merge(
            array_combine($options['default_domain'], $options['default_domain']),
            array_combine($options['domains'], $options['domains'])
        )]);
        $builder->add('translations', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, ['entry_type'     => \Lexik\Bundle\TranslationBundle\Form\Type\TranslationType::class, 'label'    => 'translations.page_title', 'required' => false, 'entry_options'  => ['data_class' => $options['translation_class']]]);
        $builder->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'translations.save']);
        $builder->add('save_add', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'translations.save_add']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class'         => null, 'default_domain'     => ['messages'], 'domains'            => [], 'translation_class'  => null, 'translation_domain' => 'LexikTranslationBundle']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'lxk_trans_unit';
    }
}
