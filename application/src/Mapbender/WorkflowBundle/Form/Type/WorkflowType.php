<?php

namespace Mapbender\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkflowType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'workflow';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'hidden')
            ->add('name', 'text', array(
                'required' => true))
            ->add('title', 'text', array(
                'required' => true))
            ->add('description', 'textarea', array(
                'required' => false));
    }
}
