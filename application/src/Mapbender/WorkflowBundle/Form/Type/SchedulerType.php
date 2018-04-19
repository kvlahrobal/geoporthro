<?php

namespace Mapbender\WorkflowBundle\Form\Type;

use Mapbender\WorkflowBundle\Entity\Scheduler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Mapbender SchedulerType
 *
 * @author Paul Schmidt
 */
class SchedulerType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'scheduler';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'workflow' => null,
            'interval' => 0
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $intervals = array(
            Scheduler::INTERVAL_0 => Scheduler::INTERVAL_0,
//            Scheduler::INTERVAL_120 => Scheduler::INTERVAL_120,
            Scheduler::INTERVAL_3600 => 'hourly',#Scheduler::INTERVAL_3600,
            Scheduler::INTERVAL_86400 => 'daily',#Scheduler::INTERVAL_86400
        );
        # TODO security (ACL for "workflow")
        $builder
            ->add('name', 'text', array(
                'required' => true))
            ->add('title', 'text', array(
                'required' => true))
            ->add('description', 'textarea', array(
                'required' => false))
            ->add('interval', 'choice', array(
                'required' => true,
                'choices' => $intervals))
            ->add('starttime', 'datetime', array(
                'required' => true,
                'attr' => array('data-type' => 'datetime')))
            ->add('workflow', 'entity', array(
                'class' => 'MapbenderWorkflowBundle:Workflow',
                'property' => 'title'
            ));
    }
}
