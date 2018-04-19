<?php

namespace Mapbender\WorkflowBundle\Form\EventListener;

use Mapbender\WorkflowBundle\Entity\TaskOption;
use Mapbender\WorkflowBundle\Form\Type\TaskOptionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * TaskOptionSubscriber class
 */
class TaskOptionSubscriber implements EventSubscriberInterface
{
    /**
     * A TaskOptionSubscriber's Factory
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $factory;

    /**
     * Creates an instance
     * @param \Symfony\Component\Form\FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Returns defined events
     * @return array events
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
//            FormEvents::PRE_SUBMIT => 'preSubmitData'
        );
    }

    /**
     * Presubmits a form data
     * @param FormEvent $event
     * @return type
     */
    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        if ($data && $data instanceof TaskOption) {
            ;
        }
    }

    /**
     * Presets a form data
     * @param FormEvent $event
     * @return type
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        if ($data && $data instanceof TaskOption) {
            $this->addFields($form, $data, $event);
        }
    }

    /**
     * Adds fields into a form.
     * @param type $form
     * @param type $taskOption
     * @param type $event
     */
    private function addFields($form, TaskOption $taskOption, $event)
    {
        $form->add($this->factory->createNamed('id', 'hidden', null, array('auto_initialize' => false)));
        if (is_string($taskOption->getValue())) {
            $form->add($this->factory->createNamed(
                'value',
                'text',
                null,
                array(
                    'auto_initialize' => false,
                    'label' => $taskOption->getName()
                )
            ));
        } elseif (is_bool($taskOption->getValue())) {
            $form->add($this->factory->createNamed(
                'value',
                'checkbox',
                null,
                array(
                    'auto_initialize' => false,
                    'label' => $taskOption->getName()
                )
            ));
        } else {
            throw new \Exception("This datatype is not yet supported.");
        }
        if ($taskOption->getChildren()->count() > 0) {
            $form->add($this->factory->createNamed(
                'children',
                'collection',
                null,
                array(
                    'type' => new TaskOptionType(),
                    'auto_initialize' => false
                )
            ));
        }
    }
}
