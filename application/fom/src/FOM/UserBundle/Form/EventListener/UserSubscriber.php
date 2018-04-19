<?php

namespace FOM\UserBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use FOM\UserBundle\Entity\User;

/**
 *
 */
class UserSubscriber implements EventSubscriberInterface
{

    /**
     * A FormFactoryInterface 's Factory
     *
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $factory;

    /**
     *
     * @var FOM\UserBundle\Entity\User
     */
    private $currentUser;

    /**
     * @inheritdoc
     */
    public function __construct(FormFactoryInterface $factory, User $currentUser = null)
    {
        $this->factory = $factory;
        $this->currentUser = $currentUser;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT => 'submit',
        );
    }


    /**
     * Checkt form fields by SUBMIT FormEvent
     *
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        $user = $event->getData();
        if (null === $user) {
            return;
        }
        if ($this->currentUser !== null && $this->currentUser !== $user && $event->getForm()->has('activated')) {
            $activated = $event->getForm()->get('activated')->getData();
            if ($activated && $user->getRegistrationToken()) {
                $user->setRegistrationToken(null);
            } elseif (!$activated && !$user->getRegistrationToken()) {
                $user->setRegistrationToken(hash("sha1",rand()));
            }
        }
    }

    /**
     * Checkt form fields by PRE_SET_DATA FormEvent
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $user = $event->getData();
        if (null === $user) {
            return;
        }
        if($this->currentUser !== null && $this->currentUser !== $user) {
            $event->getForm()->add($this->factory->createNamed('activated', 'checkbox', null, array(
                'data' => $user->getRegistrationToken() ? false : true,
                'auto_initialize' => false,
                'label' => 'Activated',
                'required' => false,
                'mapped' => false)));
        }
    }
}
