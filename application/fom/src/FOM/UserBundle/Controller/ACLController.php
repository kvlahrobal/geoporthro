<?php

namespace FOM\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOM\UserBundle\Form\Type\ACLType;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ACLController extends Controller
{
    /**
     * @Route("/acl")
     * @Template
     */
    public function indexAction()
    {
        return array('classes' => $this->getACLClasses());
    }

    /**
     * @Route("/acl/edit")
     * @Method("GET")
     * @Template
     */
    public function editAction()
    {
        // ACL access check
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');
        if(false === $securityContext->isGranted('EDIT', $oid)) {
            throw new AccessDeniedException();
        }

        $class = $this->get('request')->get('class');
        $acl_classes = $this->getACLClasses();
        if(!array_key_exists($class, $acl_classes)) {
            throw $this->createNotFoundException('No manageable class given.');
        }

        $form = $this->getClassACLForm($class);

        return array(
            'class' => $class,
            'class_name' => $acl_classes[$class],
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * @Route("/acl/edit")
     * @Method("POST")
     * @Template
     */
    public function updateAction()
    {
        // ACL access check
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Symfony\Component\Security\Acl\Domain\Acl');
        if(false === $securityContext->isGranted('EDIT', $oid)) {
            throw new AccessDeniedException();
        }

        $class = $this->get('request')->get('class');
        $acl_classes = $this->getACLClasses();
        if(!array_key_exists($class, $acl_classes)) {
            throw $this->createNotFoundException('No manageable class given.');
        }

        $form = $this->getClassACLForm($class);
        $request = $this->getRequest();
        $form->bind($request);
        if($form->isValid()) {
            $aclManager = $this->get('fom.acl.manager');
            $aclManager->setClassACLFromForm($class, $form, 'object');

            return $this->redirect($this->generateUrl('fom_user_acl_index'));
        }

        $this->get('session')->getFlashBag()->set('error',
            'Your form has errors, please review them below.');

        return array(
            'class' => $class,
            'class_name' => $acl_classes[$class],
            'form' => $form,
            'form_name' => $form->getName());
    }

    /**
     * @Route("/acl/overview")
     * @Method({ "GET" })
     * @Template("FOMUserBundle:ACL:groups-and-users.html.twig")
     */
    public function overviewAction(){
        $idProvider = $this->get('fom.identities.provider');
        $groups = $idProvider->getAllGroups();
        $users  = $idProvider->getAllUsers();
        return array('groups' => $groups, 'users' => $users);
    }

    public function getClassACLForm($class)
    {
        return $this->createForm(new ACLType(
            $this->get('security.context'),
            $this->get('security.acl.provider'),
            $this->get('router')), array(), array(
                'mapped' => false,
                'class' => $class,
                'permissions' => 'standard::class',
                'create_standard_permissions' => false
            ));
    }

    /**
     * ACL Security Identity typeahead callback
     * If query starts with 'u:', look for user, if it starts with 'r:', look
     * for role, otherwise look for both.
     *
     * @Route("/acl/sid")
     */
    public function aclsidAction()
    {
        $query = $this->get('request')->get('query');
        $response = array();
        $idProvider = $this->get('fom.identities.provider');

        if($query !== null) {
            switch(substr($query, 0, 2)) {
                case 'u:':
                    $response = $idProvider->getUsers(substr($query, 2));
                    break;
                case 'r:':
                    $response = $idProvider->getRoles(substr($query, 2));
                    break;
                default:
                    $response = array_merge(
                        $idProvider->getUsers(substr($query, 3)),
                        $idProvider->getRoles(substr($query, 3)));
            }
        }

        return new Response(json_encode($response), 200, array(
            'Content-Type' => 'application/json'));
    }

    protected function getACLClasses()
    {
        $acl_classes = array();
        foreach($this->get('kernel')->getBundles() as $bundle) {
            if(is_subclass_of($bundle, 'FOM\ManagerBundle\Component\ManagerBundle')) {
                $classes = $bundle->getACLClasses();
                if($classes) {
                    $acl_classes = array_merge($acl_classes, $classes);
                }
            }
        }
        return $acl_classes;
    }
}
