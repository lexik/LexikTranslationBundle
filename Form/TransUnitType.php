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
     * @var array
     */
    protected $domains;

    /**
     * Construct.
     *
     * @param array $domains
     */
    public function __construct(array $domains = null)
    {
        $this->domains = (null != $domains) ? $domains : array('messages');
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('key');
        $builder->add('domain', 'choice', array(
            'choices' => array_combine($this->domains, $this->domains),
        ));
        $builder->add('translations', 'collection', array(
            'type' => new TranslationType(),
            'required' => false,
        ));
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::getDefaultOptions()
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Lexik\Bundle\TranslationBundle\Entity\TransUnit',
        );
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