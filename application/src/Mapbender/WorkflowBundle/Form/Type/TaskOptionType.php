<?php

namespace Mapbender\WorkflowBundle\Form\Type;

use Mapbender\WorkflowBundle\Form\DataTransformer\TaskOptionTransformer;
use Mapbender\WorkflowBundle\Form\EventListener\TaskOptionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TaskOptionType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'taskoption';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber  = new TaskOptionSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
        $transformer = new TaskOptionTransformer();
        $builder->addModelTransformer($transformer);
    }
}
