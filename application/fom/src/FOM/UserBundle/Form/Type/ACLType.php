<?php
namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;

/**
 * ACL form type
 *
 * This type embeds a ACL configuration form which can be used to manage object
 * and class ACEs.
 *
 * Two sets of standard permissions are available to show for each ACE:
 * - "standard::object": Standard permissions for objects
 * - "standard::class": Standard permissions for classes
 * These can be provided using the 'permissions' parameter which can
 * alternatively also provided an array of permissions where keys are 1-30
 * (bitmask position) and the values the labels to show in the form.
 *
 * @author Christian Wygoda
 */
class ACLType extends AbstractType
{
    protected $securityContext;
    protected $aclProvider;
    protected $router;

    public function __construct(SecurityContext $securityContext,
        AclProviderInterface $aclProvider, $router)
    {
        $this->securityContext = $securityContext;
        $this->aclProvider = $aclProvider;
        $this->router = $router;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'acl';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        try {
            if ($options['class'] && class_exists($options['class'])) {
                $oid = new ObjectIdentity('class', $options['class']);
                $acl = $this->aclProvider->findAcl($oid);
                $aces = $acl->getClassAces();
            } else {
                $oid = ObjectIdentity::fromDomainObject($options['data']);
                $acl = $this->aclProvider->findAcl($oid);
                $aces = $acl->getObjectAces();
            }

            $isMaster = $this->securityContext->isGranted('MASTER');
            $isOwner = $this->securityContext->isGranted('OWNER');
        } catch (\Exception $e) {
            $isMaster = true;
            $isOwner = true;
            $aces = array ();
            if (true === $options['create_standard_permissions']) {
                // for unsaved entities, fake three standard permissions:
                // - Owner access for current user
                // - View access for anonymous users
                // - View access for logged in users
                $oid = null;
                $aces = array ();

                $owner = $this->securityContext->getToken()->getUser();
                $ownerAccess = array (
                    'sid' => UserSecurityIdentity::fromAccount($owner),
                    'mask' => MaskBuilder::MASK_OWNER);

                $aces[] = $ownerAccess;

                if ($options['standard_anon_access']) {
                    $anon = new RoleSecurityIdentity('IS_AUTHENTICATED_ANONYMOUSLY');
                    $anonAccess = array (
                        'sid' => $anon,
                        'mask' => MaskBuilder::MASK_VIEW);

                    $user = new RoleSecurityIdentity('ROLE_USER');
                    $userAccess = array (
                        'sid' => $anon,
                        'mask' => MaskBuilder::MASK_VIEW);
                    $aces[] = $anonAccess;
                }
            }
        }

        $permissions = is_string($options['permissions']) ? $this->getStandardPermissions($options,
                $isMaster, $isOwner) : array ('show' => $options['permissions']);

        $aceOptions = array (
            'type' => 'ace',
            'label' => 'Permissions',
            'allow_add' => true,
            'allow_delete' => true,
            'auto_initialize' => false,
            'prototype' => true,
            'options' => array ('available_permissions' => $permissions['show']),
            'mapped' => false,
            'data' => $aces);

        $builder->add('ace', 'collection', $aceOptions);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array (
            'permissions' => array (),
            'class' => null,
            'create_standard_permissions' => true,
            'standard_anon_access' => true,
            'user' => null,
            'force_master' => false,
            'force_owner' => false
        ));
    }

    /**
     * Get standard permission sets for provided string identifier.
     *
     * Returns an array with an array of permissions to show (['show']) and an
     * array with permissions which should be disabled (that means, still shown,
     * but uneditable) (['disable']).
     *
     * @param  array  $options Form options
     * @param  [type] $master  is master permission assumed?
     * @param  [type] $owner   is owner permission assumed?
     * @return [type]          Array with permissions to show and disable
     */
    protected function getStandardPermissions(array $options, $master, $owner)
    {
        switch ($options['permissions']) {
            case 'standard::object':
                $disable = array ();
                // if not owner or master, disable all permissions
                if (!$master && !$owner) {
                    $disable = array (1, 3, 4, 6, 7, 8);
                }
                // if not master, disable
                // 5 -> undelete is not used
                return array (
                    'show' => array (
                        1 => 'View',
                        3 => 'Edit',
                        4 => 'Delete',
                        6 => 'Operator',
                        7 => 'Master',
                        8 => 'Owner'
                    ),
                    'disable' => $disable);
                break;
            case 'standard::class':
                return array (
                    'show' => array (
                        1 => 'View',
                        2 => 'Create',
                        3 => 'Edit',
                        4 => 'Delete',
                        6 => 'Operator',
                        7 => 'Master',
                        8 => 'Owner'
                ));
                break;
            default:
                throw new \RuntimeException('"' . $options['permissions'] .
                '" is not a valid standard permission set identifier');
        }
    }
}
