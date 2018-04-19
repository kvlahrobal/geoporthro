<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use FOM\CoreBundle\Doctrine\DoctrineHelper;
use FOM\UserBundle\Entity\UserLogEntry;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

/**
 * Event listener for failed logins which upscales forced wait time.
 *
 * @author Christian Wygoda
 * @author Andriy Oblivantsev
 */
class FailedLoginListener extends ContainerAware
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @param AuthenticationEvent $event
     */
    public function onLoginSuccess(AuthenticationEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onLoginFailure(AuthenticationFailureEvent $event)
    {
        /** @var Registry $doctrine */
        /** @var ContainerInterface $container */
        /** @var EntityRepository $repository */

        $container  = $this->container;
        $doctrine   = $container->get('doctrine');
        $className  = 'FOMUserBundle:UserLogEntry';
        $userName   = $container->get('request')->get('_username');
        $ipAddress  = $_SERVER["REMOTE_ADDR"];
        $repository = $doctrine->getRepository($className);
        $userInfo   = array('userName'  => $userName,
                            'ipAddress' => $ipAddress,
                            'action'    => 'login',
                            'status'    => 'fail');
        $em         = $doctrine->getManager();

        if ($container->getParameter('fom_user.auto_create_log_table')) {
            DoctrineHelper::checkAndCreateTableByEntityName($container, $className);
        }

        // Log failed login attempt
        $em->persist(new UserLogEntry(array_merge(array(
            'context' => array('userAgent' => $_SERVER["HTTP_USER_AGENT"]),
        ), $userInfo)));
        $em->flush();

        $failedLoginCount = $repository->createQueryBuilder('p')->select('count(p.id)')
            ->where('p.ipAddress = :ipAddress')
            ->andWhere('p.userName = :userName')
            ->andWhere('p.status = :status')
            ->andWhere('p.action = :action')
            ->andWhere('p.creationDate > :creationDate')
            ->setParameters(array_merge(array(
                "creationDate" => new \DateTime($container->getParameter("fom_user.login_check_log_time"))
            ), $userInfo))
            ->getQuery()
            ->getSingleScalarResult();

        if ($failedLoginCount >= $container->getParameter("fom_user.login_attempts_before_delay")) {
            sleep($container->getParameter("fom_user.login_delay_after_fail"));
        }

        // Garbage collection for log entries
        // TODO: create user log service and refactor here.
        $repository->createQueryBuilder('p')
            ->delete()
            ->where('p.creationDate < :gcDate')
            ->setParameters(array(
                "gcDate" => new \DateTime("-2 days")
            ))
            ->getQuery()
            ->getSingleScalarResult();
    }

}
