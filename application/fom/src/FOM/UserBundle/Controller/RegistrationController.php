<?php

namespace FOM\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Form\Type\UserRegistrationType;
use FOM\UserBundle\Form\Type\UserForgotPassType;
use FOM\UserBundle\Security\UserHelper;

/**
 * Self registration controller.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */

class RegistrationController extends Controller
{
    /**
     * Check if self registration is allowed.
     *
     * setContainer is called after controller creation is used to deny access to controller if self registration has
     * been disabled.
     */
    public function setContainer(ContainerInterface $container = NULL)
    {
        parent::setContainer($container);

        if(!$this->container->getParameter('fom_user.selfregister'))
            throw new AccessDeniedHttpException();
    }

    /**
     * Registration step 3: Show instruction page that email has been sent
     *
     * @Route("/user/registration/send")
     * @Method("GET")
     * @Template
     */
    public function sendAction()
    {
        return array();
    }

    /**
     * Registration step 1: Registration form
     *
     * @Route("/user/registration")
     * @Method("GET")
     * @Template
     */
    public function formAction()
    {
        $user = new User();
        $form = $this->createForm(new UserRegistrationType(), $user);

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Registration step 2: Create user and set registration token
     *
     * @Route("/user/registration")
     * @Method("POST")
     * @Template("FOMUserBundle:Registration:form.html.twig")
     */
    public function register()
    {
        $user = new User();
        $form = $this->createForm(new UserRegistrationType(), $user);
        $form->bind($this->get('request'));

        //@TODO: Check if username and email are unique

        if($form->isValid()) {
            $helper = new UserHelper($this->container);
            $helper->setPassword($user, $user->getPassword());

            $user->setRegistrationToken(hash("sha1",rand()));
            $user->setRegistrationTime(new \DateTime());

            $groupRepository = $this->getDoctrine()->getRepository('FOMUserBundle:Group');
            foreach($this->container->getParameter('fom_user.self_registration_groups') as $groupTitle) {
                $group = $groupRepository->findOneByTitle($groupTitle);
                if($group) {
                    $user->addGroups($group);
                } else {
                    $msg = sprintf('Self-registration group "%s" not found for user "%s"',
                        $groupTitle,
                        $user->getUsername());
                    $this->get('logger')->err($msg);
                }

            }

            $this->sendEmail($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $helper->giveOwnRights($user);

            return $this->redirect($this->generateUrl('fom_user_registration_send'));
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Registration step 4: Activate account by token
     *
     * @Route("/user/activate")
     * @Method("GET")
     */
    public function confirmAction()
    {
        $token = $this->get('request')->get('token');
        if(!$token) {
            return $this->render('FOMUserBundle:Login:error-notoken.html.twig');
        }

        // Lookup token
        $user = $this->getDoctrine()->getRepository("FOMUserBundle:User")->findOneByRegistrationToken($token);
        if(!$user) {
            $mail = $this->container->getParameter('fom_user.mail_from_address');
            return $this->render('FOMUserBundle:Login:error-notoken.html.twig', array(
                'site_email' => $mail));
        }

        // Check token age
        $max_registration_age = $this->container->getParameter("fom_user.max_registration_time");
        if(!$this->checkTimeInterval($user->getRegistrationTime(), $max_registration_age)) {
            $form = $this->createForm('form');
            return $this->render('FOMUserBundle:Login:error-tokenexpired.html.twig', array(
                'user' => $user,
                'form' => $form->createView()
            ));
        }

        // Unset token
        $em = $this->getDoctrine()->getManager();
        $user->setRegistrationToken(null);
        $em->flush();

        // Forward to final page
        return $this->redirect($this->generateUrl('fom_user_registration_done'));
    }

    /**
     * Registration step 4a: Reset token (if expired)
     *
     * @Route("/user/registration/reset")
     * @Method("POST")
     */
    public function reset()
    {
        // Lookup token
        $token = $this->get('request')->get('token');
        if(!$token) {
            return $this->render('FOMUserBundle:Login:error-notoken.html.twig');
        }

        $user = $this->getDoctrine()->getRepository("FOMUserBundle:User")->findOneByRegistrationToken($token);
        if(!$user) {
            //@TODO: Get site email from configuration
            return $this->render('FOMUserBundle:Login:error-notoken.html.twig', array(
                'site_email' => 'FOFO'));
        }

        $user->setRegistrationToken(hash("sha1",rand()));
        $user->setRegistrationTime(new \DateTime());

        $this->sendEmail($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->redirect($this->generateUrl('fom_user_registration_send'));
    }

    /**
     * Registration step 5: Welcome new user
     *
     * @Route("/user/registration/done")
     * @Method("GET")
     * @Template
     */
    public function doneAction()
    {
        return array();
    }

    protected function checkTimeInterval($startTime, $timeInterval){
        $checktime = new \DateTime();
        $checktime->sub(new \DateInterval(sprintf("PT%dH",$timeInterval)));
        if($startTime < $checktime) {
            return false;
        } else{
            return true;
        }
    }

    protected function sendEmail($user)
    {
       $fromName = $this->container->getParameter("fom_user.mail_from_name");
       $fromEmail = $this->container->getParameter("fom_user.mail_from_address");
       $mailFrom = array($fromEmail => $fromName);
       $mailer = $this->get('mailer');
       $text = $this->get("templating")->render('FOMUserBundle:Registration:email-body.text.twig', array("user" => $user));
       $html = $this->get("templating")->render('FOMUserBundle:Registration:email-body.html.twig', array("user" => $user));
       $message = \Swift_Message::newInstance()
           ->setSubject($this->get("templating")->render('FOMUserBundle:Registration:email-subject.text.twig'))
           ->setFrom($mailFrom)
           ->setTo($user->getEmail())
           ->setBody($text)
           ->addPart($html, 'text/html');

       $mailer->send($message);
    }
}
