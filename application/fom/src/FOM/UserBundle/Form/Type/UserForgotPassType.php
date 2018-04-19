<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;

class UserForgotPassType extends AbstractType
{
    public function getName()
    {
        return 'passwordresetrequest';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search', 'text', array(
                'label' => 'Username or Email',
                'attr' => array(
                    'autofocus' => 'on')));

    }
}

