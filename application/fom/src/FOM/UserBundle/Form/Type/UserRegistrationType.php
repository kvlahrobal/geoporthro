<?php
namespace FOM\UserBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of UserType
 *
 * @author apour
 * @author Christian Wygoda
 */
class UserRegistrationType extends AbstractType
{
    public function getName()
    {
        return "User";
    }

    public function buildForm(FormBuilderInterface $builder,array $options)
    {
        $builder->add("username","text",array(
            "required" => true,
            'attr' => array(
                'autofocus' => 'on'
            )
        ));

        $builder->add('password', 'repeated', array(
            'type' => 'password',
            'invalid_message' => 'The password fields must match.',
            'options' => array('label' => 'Password'),
        ));


        $builder->add("email","email",array(
            "required" => true
        ));
    }
}

