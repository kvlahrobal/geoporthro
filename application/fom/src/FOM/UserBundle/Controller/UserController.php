<?php

namespace FOM\UserBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Form\Type\UserType;
use FOM\UserBundle\Security\UserHelper;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * User management controller
 *
 * @author Christian Wygoda
 */
class UserController extends Controller
{
    /**
     * Renders user list.
     *
     * @ManagerRoute("/user")
     * @Method({ "GET" })
     * @Template
     */
    public function indexAction()
    {
        $securityContext = $this->get('security.context');
        $oid             = new ObjectIdentity('class', 'FOM\UserBundle\Entity\User');

        $query         = $this->getDoctrine()->getManager()->createQuery('SELECT r FROM FOMUserBundle:User r');
        $users         = $query->getResult();
        $allowed_users = array();
        // ACL access check
        foreach ($users as $index => $user) {
            if ($securityContext->isGranted('VIEW', $user)) {
                $allowed_users[] = $user;
            }
        }

        return array(
            'users'             => $allowed_users,
            'create_permission' => $securityContext->isGranted('CREATE', $oid));
    }

    /**
     * @ManagerRoute("/user/new")
     * @Method({ "GET" })
     * @Template("FOMUserBundle:User:form.html.twig")
     */
    public function newAction()
    {
        $user = new User();

        // ACL access check
        $securityContext = $this->get('security.context');
        $oid             = new ObjectIdentity('class', get_class($user));
        if (false === $securityContext->isGranted('CREATE', $oid)) {
            throw new AccessDeniedException();
        }

        $groupPermission =
            $securityContext->isGranted('EDIT', new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group'))
            || $securityContext->isGranted('OWNER', $oid);

        $profile = $this->addProfileForm($user);
        $form    = $this->createForm(new UserType(), $user, array(
            'profile_formtype' => $profile['formtype'],
            'group_permission' => $groupPermission,
            'acl_permission'   => $securityContext->isGranted('OWNER', $oid),
        ));

        return array(
            'user'             => $user,
            'form'             => $form->createView(),
            'form_name'        => $form->getName(),
            'edit'             => false,
            'profile_template' => $profile['template'],
            'profile_assets'   => $profile['assets']);
    }

    /**
     * @ManagerRoute("/user")
     * @Method({ "POST" })
     * @Template("FOMUserBundle:User:form.html.twig")
     */
    public function createAction()
    {
        $user = new User();

        // ACL access check
        $securityContext = $this->get('security.context');
        $oid             = new ObjectIdentity('class', get_class($user));
        if (false === $securityContext->isGranted('CREATE', $oid)) {
            throw new AccessDeniedException();
        }

        $groupPermission =
            $securityContext->isGranted('EDIT', new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group'))
            || $securityContext->isGranted('OWNER', $oid);

        $profile = $this->addProfileForm($user);
        $form    = $this->createForm(new UserType(), $user, array(
            'profile_formtype' => $profile['formtype'],
            'group_permission' => $groupPermission,
            'acl_permission'   => $securityContext->isGranted('OWNER', $oid),
        ));

        $form->bind($this->get('request'));

        if ($form->isValid()) {
            // Set encrypted password and create new salt
            // The unencrypted password is already set on the user!
            $helper = new UserHelper($this->container);
            $helper->setPassword($user, $user->getPassword());

            $user->setRegistrationTime(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();

            try {
                $em->getConnection()->beginTransaction();

                $profile = $user->getProfile();
                $user->setProfile(null);
                $em->persist($user);

                // SQLite needs a flush here
                $em->flush();

                // Check and persists profile if exists
                if ($profile) {
                    $profile->setUid($user);
                    $em->persist($profile);
                }

                $em->flush();

                $em->getConnection()->commit();

                if ($form->has('acl')) {
                    $aclManager = $this->get('fom.acl.manager');
                    $aclManager->setObjectACLFromForm($user, $form->get('acl'), 'object');
                }

                $em->flush();

                // Make sure, the new user has VIEW & EDIT permissions
                $helper->giveOwnRights($user);

                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                throw $e;
            }

            $this->get('session')->getFlashBag()->set('success', 'The user has been saved.');

            return $this->redirect($this->generateUrl('fom_user_user_index'));
        }

        $this->get('session')->getFlashBag()->set('error', 'There are field validation errors.');

        return array(
            'user'             => $user,
            'form'             => $form->createView(),
            'form_name'        => $form->getName(),
            'edit'             => false,
            'profile_template' => $profile['template'],
            'profile_assets'   => $profile['assets']);
    }

    /**
     * @ManagerRoute("/user/{id}/edit")
     * @Method({ "GET" })
     * @Template("FOMUserBundle:User:form.html.twig")
     */
    public function editAction($id)
    {
        $user = $this->getDoctrine()->getRepository('FOMUserBundle:User')->find($id);
        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        // ACL access check
        $securityContext = $this->get('security.context');
        if (false === $securityContext->isGranted('EDIT', $user)) {
            throw new AccessDeniedException();
        }

        $groupPermission =
            $securityContext->isGranted('EDIT', new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group'))
            || $securityContext->isGranted('OWNER', $user);

        $profile = $this->addProfileForm($user);
        $form    = $this->createForm(new UserType(), $user, array(
            'requirePassword'  => false,
            'profile_formtype' => $profile['formtype'],
            'group_permission' => $groupPermission,
            'acl_permission'   => $securityContext->isGranted('OWNER', $user),
            'currentUser' => $securityContext->getToken()->getUser()
        ));

        return array(
            'user'             => $user,
            'form'             => $form->createView(),
            'form_name'        => $form->getName(),
            'edit'             => true,
            'profile_template' => $profile['template'],
            'profile_assets'   => $profile['assets']);
    }

    /**
     * @ManagerRoute("/user/{id}/update")
     * @Method({ "POST" })
     * @Template("FOMUserBundle:User:form.html.twig")
     */
    public function updateAction($id)
    {
        $user = $this->getDoctrine()->getRepository('FOMUserBundle:User')->find($id);
        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }

        // ACL access check
        $securityContext = $this->get('security.context');
        if (false === $securityContext->isGranted('EDIT', $user)) {
            throw new AccessDeniedException();
        }
        // If no password is given, we'll recycle the old one
        $request      = $this->get('request');
        $userData     = $request->get('user');
        $keepPassword = false;
        if ($userData['password']['first'] === '' && $userData['password']['second'] === '') {
            $userData['password'] = array(
                'first'  => $user->getPassword(),
                'second' => $user->getPassword());

            $keepPassword = true;
        }
        if (!array_key_exists('username', $userData)) {
            $userData['username'] = $user->getUsername();
        }

        $groupPermission =
            $securityContext->isGranted('EDIT', new ObjectIdentity('class', 'FOM\UserBundle\Entity\Group'))
            || $securityContext->isGranted('OWNER', $user);

        $profile = $this->addProfileForm($user);
        $form    = $this->createForm(new UserType(), $user, array(
            'requirePassword'  => false,
            'profile_formtype' => $profile['formtype'],
            'group_permission' => $groupPermission,
            'acl_permission'   => $securityContext->isGranted('OWNER', $user),
            'currentUser' => $securityContext->getToken()->getUser()
        ));
        $form->bind($userData);

        if ($form->isValid()) {
            if (!$keepPassword) {
                // Set encrypted password and create new salt
                // The unencrypted password is already set on the user!
                $helper = new UserHelper($this->container);
                $helper->setPassword($user, $user->getPassword());
            }

            $em = $this->getDoctrine()->getManager();

            // This is the same check as abote in createForm for acl_permission
            if ($securityContext->isGranted('OWNER', $user)) {
                $aclManager = $this->get('fom.acl.manager');
                $aclManager->setObjectACLFromForm($user, $form->get('acl'), 'object');
            }

            $user->getProfile()->setUid($user);
            $em->flush();

            $this->get('session')->getFlashBag()->set('success', 'The user has been updated.');

            return $this->redirect($this->generateUrl('fom_user_user_index'));

        }

        return array(
            'user'             => $user,
            'form'             => $form->createView(),
            'form_name'        => $form->getName(),
            'edit'             => true,
            'profile_template' => $profile['template'],
            'profile_assets'   => $profile['assets']);
    }

    /**
     * @ManagerRoute("/user/{id}/delete")
     * @Method({ "POST" })
     *
     * @todo : Delete ACEs for given user
     */
    public function deleteAction($id)
    {
        $user = $this->getDoctrine()->getRepository('FOMUserBundle:User')->find($id);

        if ($user === null) {
            throw new NotFoundHttpException('The user does not exist');
        }
        if ($user->getId() === 1) {
            throw new NotFoundHttpException('The root user can not be deleted');
        }

        // ACL access check
        $securityContext = $this->get('security.context');
        if (false === $securityContext->isGranted('DELETE', $user)) {
            throw new AccessDeniedException();
        }

        $aclProvider = $this->get('security.acl.provider');
        $oid         = ObjectIdentity::fromDomainObject($user);
        $aclProvider->deleteAcl($oid);

        $this->addProfileForm($user);
        $profile = $user->getProfile();
        $request = $this->getRequest();

        try {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();

            $aclProvider = $this->get('security.acl.provider');
            $oid         = ObjectIdentity::fromDomainObject($user);
            $aclProvider->deleteAcl($oid);

            $em->remove($user);
            if ($user->getProfile()) {
                $em->remove($user->getProfile());
            }
            $em->flush();
            $em->getConnection()->commit();

            $this->get('session')->getFlashBag()->set('success', 'The user has been deleted.');
        } catch (Exception $e) {
            $em->getConnection()->rollback();
            $this->get('session')->getFlashBag()->set('error', "The user couldn't be deleted.");
        }
        return new Response();
    }

    private function addProfileForm(User $user)
    {
        $container       = $this->container;
        $profileEntity   = $container->getParameter('fom_user.profile_entity');
        $profileFormType = $container->getParameter('fom_user.profile_formtype');
        $profileTemplate = $container->getParameter('fom_user.profile_template');
        $profileAssets   = $container->getParameter('fom_user.profile_assets');

        return array(
            'formtype' => $profileFormType,
            'template' => $profileTemplate,
            'assets'   => $profileAssets
        );
    }
}
