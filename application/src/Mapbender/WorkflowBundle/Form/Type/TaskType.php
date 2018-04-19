<?php

namespace Mapbender\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TaskType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'task';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'workflow' => null,
            'title' => 'TaskType',
            'class' => null,
            'options' => array(),
            'priority' => 0
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'hidden')
            ->add('class', 'hidden')
            ->add('title', 'text', array(
                'required' => true))
            ->add('priority', 'hidden', array(
                'required' => true))
            ->add('options', 'collection', array(
                'type' => new TaskOptionType(),
                'auto_initialize' => false));
    }
}
