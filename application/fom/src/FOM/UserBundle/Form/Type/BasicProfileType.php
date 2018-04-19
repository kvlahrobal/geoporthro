<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FOM\UserBundle\Entity\BasicProfile;

class BasicProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roles = BasicProfile::getOrganizationRoleChoices();

        $builder
            ->add('firstName', 'text', array(
                'required' => false))
            ->add('lastName', 'text', array(
                'required' => false))
            ->add('notes', 'text', array(
                'required' => false))
            ->add('phone', 'text', array(
                'required' => false))
            ->add('street', 'text', array(
                'required' => false))
            ->add('zipCode', 'text', array(
                'required' => false))
            ->add('city', 'text', array(
                'required' => false))
            ->add('country', 'text', array(
                'required' => false))
            ->add('organizationName', 'text', array(
                'required' => false))
            ->add('organizationRole', 'choice', array(
                'choices' => $roles,
                'empty_value' => 'Choose an option...',
                'required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'FOM\UserBundle\Entity\BasicProfile',
        ));
    }

    public function getName()
    {
        return 'profile';
    }
}
