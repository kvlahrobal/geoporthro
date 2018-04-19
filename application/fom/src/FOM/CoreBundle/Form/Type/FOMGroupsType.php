<?php

namespace FOM\CoreBundle\Form\Type;

use FOM\CoreBundle\Form\DataTransformer\GroupIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class FOMGroupsType extends AbstractType
{
    /**
     *
     * @var type
     */
    protected $container;
    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /**
     * @inheritdoc
     */
    public function getContainer()
    {
        return $this->container;
    }
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'fom_groups';
    }
    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'entity';
    }
    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $type = $this;
        $resolver->setDefaults(array(
            'user_groups' => False,
            'return_entity' => False,
            'compound' => false,
            'class' => 'FOMUserBundle:Group',
            'property' => 'title',
            'query_builder' => function(Options $options) use ($type) {
                $builderName = preg_replace("/[^\w]/", "", $options['property_path']);
                $repository = $type->getContainer()->get('doctrine')->getRepository("FOMUserBundle:Group");
                $qb = $repository->createQueryBuilder($builderName);
                if($options['user_groups'])
                {
                    $securityContext = $type->getContainer()->get('security.context');
                    $user = $securityContext->getToken()->getUser();
                    if(is_object($user)) {
                        $qb->join($builderName . '.users', 'u', 'WITH', 'u.id = :uid')
                            ->setParameter('uid', $user->getId());
                    }
                }
                return $qb;
            }
        ));
    }
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($options['return_entity'] === false)
        {
            $entityManager = $this->container->get('doctrine')->getManager();
            $transformer = new GroupIdTransformer($entityManager);
            $builder->addModelTransformer($transformer);
        }
    }
}