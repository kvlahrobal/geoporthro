<?php

namespace FOM\UserBundle\Component;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * ACL Manager service implementation
 *
 * This manager is available as a service called 'fom.acl.manager' and is meant
 * to be used with a form and will delete/update/add ACEs.
 *
 * @author Christian Wygoda
 */
class AclManager
{
    protected $aclProvider;

    public function __construct($aclProvider)
    {
        $this->aclProvider = $aclProvider;
    }

    /**
     * Update ACEs from a form of class FOM\UserBundle\Form\Type\ACLType
     * (commonly 'acl')
     * @param object $entity Entity to update ACL for
     * @param object $form   ACLType form object
     * @param string $type   ACE type to update (object or class)
     */
    public function setObjectACLFromForm($entity, $form, $type)
    {
        $aces = $form->get('ace')->getData();
        $this->setObjectACL($entity, $aces, $type);
    }

    /**
     * Update ACEs for entity
     * @param object $entity Entity to update ACL for
     * @param array  $aces   Array with ACEs (not Entry objects!)
     * @param string $type   ACE type to update (object or class)
     */
    public function setObjectACL($entity, $aces, $type)
    {
        switch($type) {
            case 'object':
                $deleteMethod = 'deleteObjectAce';
                $insertMethod = 'insertObjectAce';
                break;
            default:
                throw new \RuntimeException('ACEs of type ' . $type
                    . ' are not supported.');
        }
        $acl = $this->getAcl($entity);
        $oldAces = $acl->getObjectAces();

        // @TODO: This is a naive implementation, we should update where
        // possible instead of deleting all old ones and adding all new ones...

        // Delete old ACEs
        foreach(array_reverse(array_keys($oldAces)) as $idx) {
            $acl->$deleteMethod(intval($idx));
        }
        $this->aclProvider->updateAcl($acl);
        // Add new ACEs
        foreach(array_reverse($aces) as $idx => $ace) {
            // If no mask is given, we might as well not insert the ACE
            if($ace['mask'] === 0) {
                continue;
            }
            $acl->$insertMethod($ace['sid'], $ace['mask']);
        }

        $this->aclProvider->updateAcl($acl);
    }

    /**
     * Update ACEs from a form of class FOM\UserBundle\Form\Type\ACLType
     * (commonly 'acl')
     * @param object $entity Entity to update ACL for
     * @param object $form   ACLType form object
     * @param string $type   ACE type to update (object or class)
     */
    public function setClassACLFromForm($class, $form)
    {
        $aces = $form->get('ace')->getData();
        $this->setClassACL($class, $aces);
    }

    protected function setClassACL($class, $aces)
    {
        $acl = $this->getAcl($class);
        $oldAces = $acl->getClassAces();

        // @TODO: This is a naive implementation, we should update where
        // possible instead of deleting all old ones and adding all new ones...

        // Delete old ACEs
        foreach(array_reverse(array_keys($oldAces)) as $idx) {
            $acl->deleteClassAce(intval($idx));
        }

        $this->aclProvider->updateAcl($acl);
        // Add new ACEs
        foreach(array_reverse($aces) as $idx => $ace) {
            // If no mask is given, we might as well not insert the ACE
            if($ace['mask'] === 0) {
                continue;
            }
            $acl->insertClassAce($ace['sid'], $ace['mask']);
        }

        $this->aclProvider->updateAcl($acl);
    }

    protected function getACL($entity)
    {
        if(is_string($entity) && class_exists($entity)) {
            $oid = new ObjectIdentity('class', $entity);
        } else {
            $oid = ObjectIdentity::fromDomainObject($entity);
        }

        try {
            $acl = $this->aclProvider->createAcl($oid);
        } catch(\Exception $e) {
            $acl = $this->aclProvider->findAcl($oid);
        }

        return $acl;
    }
}
