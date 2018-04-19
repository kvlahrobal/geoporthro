<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\DependencyInjection\Container;
use FOM\ManagerBundle\Form\Type\TagboxType;

use FOM\UserBundle\Form\DataTransformer\ACEDataTransformer;

class ACEType extends AbstractType
{
    protected $securityContext;
    protected $aclProvider;
    protected $container;

    public function __construct(SecurityContext $securityContext,
        AclProviderInterface $aclProvider, Container $container)
    {
        $this->securityContext = $securityContext;
        $this->aclProvider = $aclProvider;
        $this->container = $container;
    }

    public function getName()
    {
        return 'ace';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new ACEDataTransformer($this->container);
        $builder->addModelTransformer($transformer);

        $builder->add('sid', 'text', array(
            'required' => true,
            'label' => 'Role or user',
            'attr' => array(
                'data-provide' => 'typeahead',
                'autocomplete' => 'off')));

        $permissions = $options['available_permissions'];

        foreach ($permissions as $bit => $perm){
            $name = strtolower($perm);
            $builder->add('permission_' . $bit, new TagboxType(), array(
                'property_path' => '[permissions][' . $bit . ']',
                'attr' => array("class"=>$name)));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'available_permissions' => array()));
    }
}
