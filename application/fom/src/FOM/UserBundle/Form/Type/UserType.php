<?php

namespace FOM\UserBundle\Form\Type;

use FOM\UserBundle\Form\EventListener\UserSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
    public function getName()
    {
        return 'user';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $a = $options['currentUser'];
        $builder->addEventSubscriber(new UserSubscriber($builder->getFormFactory(), $options['currentUser']));
        $builder
            ->add('username', 'text')
            ->add('email', 'email', array(
                'label' => 'E-Mail'))
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'required' => $options['requirePassword'],
                'options' => array(
                    'label' => 'Password')));

        if (true === $options['group_permission']) {
            $builder
                ->add('groups', 'entity', array(
                    'class' =>  'FOMUserBundle:Group',
                    'query_builder' => function (EntityRepository $er) {
                        $qb = $er->createQueryBuilder('r')
                            ->add('orderBy', 'r.title ASC');
                        return $qb;
                    },
                    'expanded' => true,
                    'multiple' => true,
                    'property' => 'title',
                    'label' => 'Groups'));
        }

        if (true === $options['acl_permission']) {
            $builder
                ->add('acl', 'acl', array(
                    'mapped' => false,
                    'data' => $options['data'],
                    'permissions' => 'standard::object',
                    'standard_anon_access' => false));
        }

        if ($options['profile_formtype']) {
            $formType = $options['profile_formtype'];
            $builder->add('profile', new $formType());
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'requirePassword' => true,
            'profile_formtype' => null,
            'group_permission' => false,
            'acl_permission' => false,
            'cascade_validation' => true,
            'currentUser' => null
        ));
    }
}
