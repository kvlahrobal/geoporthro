<?php

namespace FOM\UserBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use FOM\UserBundle\DependencyInjection\Factory\LdapSecurityFactory;
use FOM\UserBundle\DependencyInjection\Factory\SspiFactory;
use FOM\ManagerBundle\Component\ManagerBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * FOMUserBundle - provides user management
 *
 * @author Christian Wygoda
 */
class FOMUserBundle extends ManagerBundle
{
    public function build(ContainerBuilder $container)
    {
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new LdapSecurityFactory());
        $extension->addSecurityListenerFactory(new SspiFactory());
    }

    /**
     * @inheritdoc
     */
    public function getManagerControllers()
    {
        $trans = $this->container->get('translator');
        return array(
            array(
                'title' => $trans->trans("fom.user.userbundle.user_control"),
                'weight' => 100,
                'route' => 'fom_user_user_index',
                'routes' => array(
                    'fom_user_user',
                    'fom_user_group',
                    'fom_user_acl'
                ),
                'subroutes' => array(
                    0 => array('title'=>$trans->trans("fom.user.userbundle.users"),
                               'route'=>'fom_user_user_index',
                               'subroutes' => array(
                                    0 => array(
                                      'title'=>$trans->trans("fom.user.userbundle.new_user"),
                                      'route'=>'fom_user_user_new',
                                      'enabled' => function($securityContext) {
                                          $oid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\User');
                                          return $securityContext->isGranted('CREATE', $oid);
                                      })
                                )
                              ),
                    1 => array('title'=>$trans->trans("fom.user.userbundle.groups"),
                               'route'=>'fom_user_group_index',
                               'subroutes' => array(
                                    0 => array(
                                      'title'=>$trans->trans("fom.user.userbundle.new_group"),
                                      'route'=>'fom_user_group_new',
                                      'enabled' => function($securityContext) {
                                          $oid = new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group');
                                          return $securityContext->isGranted('CREATE', $oid);
                                      })
                                )
                               ),
                    2 => array('title'=>$trans->trans("fom.user.userbundle.acls"),
                               'route'=>'fom_user_acl_index',
                                'enabled' => function($securityContext) {
                                    $oid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');
                                    return $securityContext->isGranted('CREATE', $oid) || $securityContext->isGranted('EDIT', $oid);
                                })
                )
            )
        );
    }

    public function getRoles()
    {
        $trans = $this->container->get('translator');
        return array(
            'ROLE_SUPER_ADMIN' => $trans->trans("fom.user.userbundle.roles.super_admin"),
            'ROLE_USER_ADMIN' => $trans->trans("fom.user.userbundle.roles.user_admin"));
    }

    public function getACLClasses()
    {
        $trans = $this->container->get('translator');
        return array(
            'Symfony\Component\Security\Acl\Domain\Acl' => $trans->trans("fom.user.userbundle.classes.acls"),
            'FOM\UserBundle\Entity\User' => $trans->trans("fom.user.userbundle.classes.users"),
            'FOM\UserBundle\Entity\Group' => $trans->trans("fom.user.userbundle.classes.groups"));
    }
}
