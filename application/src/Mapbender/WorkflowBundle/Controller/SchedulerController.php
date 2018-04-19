<?php

namespace Mapbender\WorkflowBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\WorkflowBundle\Component\CmdHandler;
use Mapbender\WorkflowBundle\Entity\Scheduler;
use Mapbender\WorkflowBundle\Form\Type\SchedulerType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Mapbender scheduler management
 *
 * @author Paul Schmidt
 */
class SchedulerController extends Controller
{

    /**
     * Render a list of applications the current logged in user has access
     * to.
     *
     * @ManagerRoute("/schedulers")
     * @Method("GET")
     * @Template
     */
    public function indexAction()
    {
        $secCntx    = $this->get('security.context');
        $schedulers = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->findAll();
        $allowed    = array();
        foreach ($schedulers as $scheduler) {
            if ($secCntx->isGranted('VIEW', $scheduler)) {
                if (!$secCntx->isGranted('OWNER', $scheduler)) {
                    continue;
                }
                $allowed[] = $scheduler;
            }
        }
        return array(
            'schedulers' => $allowed,
        );
    }

    /**
     * Shows form for creating new applications
     *
     * @ManagerRoute("/scheduler/new")
     * @Method("GET")
     * @Template
     */
    public function newAction()
    {
        $scheduler = new Scheduler();
        if (!$this->get('security.context')->isGranted('CREATE', $scheduler)) {
            throw new AccessDeniedException();
        }
        $form = $this->container->get("form.factory")->create(new SchedulerType(), $scheduler);
        return array(
            'application' => $scheduler,
            'form' => $form->createView(),
            'form_name' => $form->getName()
        );
    }

    /**
     * Create a new element from POSTed data
     *
     * @ManagerRoute("/scheduler/new")
     * @Method("POST")
     * @Template("MapbenderWorkflowBundle:Scheduler:new.html.twig")
     */
    public function createAction()
    {
        $scheduler = new Scheduler();
        if (!$this->get('security.context')->isGranted('CREATE', $scheduler)) {
            throw new AccessDeniedException();
        }

        $form    = $this->container->get("form.factory")->create(new SchedulerType(), $scheduler);
        $request = $this->getRequest();

        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $em->persist($scheduler);
            $em->flush();

//            $aclManager = $this->get('fom.acl.manager');
//            $aclManager->setObjectACLFromForm($scheduler, $form->get('acl'), 'object');
//            $em->persist($scheduler);
//            $em->flush();

            $em->getConnection()->commit();

            return $this->redirect($this->generateUrl('mapbender_workflow_scheduler_index'));
        }

        return array(
            'application' => $scheduler,
            'form' => $form->createView(),
            'form_name' => $form->getName()
        );
    }

    /**
     * @ManagerRoute("/scheduler/{sid}/edit", requirements={"sid" = "\d+"})
     * @Method("GET")
     * @Template
     */
    public function editAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if (!$this->get('security.context')->isGranted('EDIT', $scheduler)) {
            throw new AccessDeniedException();
        }

        $form = $this->container->get("form.factory")->create(new SchedulerType(), $scheduler);
        return array(
            'scheduler' => $scheduler,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Updates element by POSTed data
     *
     * @ManagerRoute("/scheduler/{sid}/edit", requirements = {"sid" = "\d+" })
     * @Method("POST")
     * @Template("MapbenderWorkflowBundle:Scheduler:edit.html.twig")
     */
    public function updateAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if (!$this->get('security.context')->isGranted('EDIT', $scheduler)) {
            throw new AccessDeniedException();
        }
        $form    = $this->container->get("form.factory")->create(new SchedulerType(), $scheduler);
        $request = $this->getRequest();
        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $em->persist($scheduler);
            $em->flush();
            $em->getConnection()->commit();
            return $this->redirect($this->generateUrl('mapbender_workflow_scheduler_index'));
        } else {
            return array(
                'scheduler' => $scheduler,
                'form' => $form->createView(),
                'form_name' => $form->getName()
            );
        }
    }

    /**
     * Delete confirmation page
     * @ManagerRoute("/scheduler/{sid}/delete")
     * @Method("GET")
     * @Template("MapbenderWorkflowBundle:Scheduler:delete.html.twig")
     */
    public function confirmDeleteAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if ($scheduler === null) {
            $this->get('session')->getFlashBag()->set('error', 'Your Scheduler has been already deleted.');
            return $this->redirect($this->generateUrl('mapbender_workflow_scheduler_index'));
        }
        if (!$this->get('security.context')->isGranted('DELETE', $scheduler)) {
            throw new AccessDeniedException();
        }
        $form = $this->createFormBuilder(array('id' => $scheduler->getId()))->add('id', 'hidden')->getForm();
        return array(
            'scheduler' => $scheduler,
            'form' => $form->createView());
    }

    /**
     * Delete task
     *
     * @ManagerRoute("/scheduler/{sid}/delete")
     * @Method("POST")
     */
    public function deleteAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if (!$this->get('security.context')->isGranted('DELETE', $scheduler)) {
            throw new AccessDeniedException();
        }
        try {
            $em          = $this->getDoctrine()->getManager();
            $aclProvider = $this->get('security.acl.provider');
            $em->getConnection()->beginTransaction();
            $oid         = ObjectIdentity::fromDomainObject($scheduler);
            $aclProvider->deleteAcl($oid);
            $em->remove($scheduler);
            $em->flush();
            $em->commit();
            $this->get('session')->getFlashBag()->set('success', 'Your scheduler has been deleted.');
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->set('error', 'Your scheduler couldn\'t be deleted.');
        }
        return $this->redirect($this->generateUrl('mapbender_workflow_scheduler_index'));
    }

    /**
     * Delete task
     *
     * @ManagerRoute("/scheduler/{sid}/report")
     * @Method("GET")
     * @Template("MapbenderWorkflowBundle:Scheduler:report.html.twig")
     */
    public function reportAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if (!$this->get('security.context')->isGranted('VIEW', $scheduler)) {
            throw new AccessDeniedException();
        }
        return array('scheduler' => $scheduler);
    }

    /**
     * Delete task
     *
     * @ManagerRoute("/scheduler/{sid}/start")
     * @Method("GET")
     */
    public function startAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if (!$this->get('security.context')->isGranted('EDIT', $scheduler)) {
            throw new AccessDeniedException();
        }
        // start Unix
        $pids = CmdHandler::getProcess($this->getSchedulerCmd($scheduler));
        if (CmdHandler::isUnix() && count($pids) === 0) {
            $scheduler->setStatus(Scheduler::STATUS_WAITING);
            $em = $this->getDoctrine()->getManager();
            $em->persist($scheduler);
            $em->flush();
            CmdHandler::runProcessFromApp($this->container, $this->getSchedulerCmd($scheduler, true));
            $this->get('session')->getFlashBag()
                ->set('success', 'Scheduler "' . $scheduler->getName() . '" has been started');
        } else {
            $this->get('session')->getFlashBag()
                ->set('error', 'Scheduler "' . $scheduler->getName() . '" is allready in use.');
        }
        return $this->redirect($this->generateUrl('mapbender_workflow_scheduler_index'));
    }

    /**
     * Delete task
     *
     * @ManagerRoute("/scheduler/{sid}/stop")
     * @Method("GET")
     */
    public function stopAction($sid)
    {
        $scheduler = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Scheduler")->find($sid);
        if (!$this->get('security.context')->isGranted('EDIT', $scheduler)) {
            throw new AccessDeniedException();
        }
        $scheduler->setStatus(Scheduler::STATUS_ABORTED);
        $em = $this->getDoctrine()->getManager();
        $em->persist($scheduler);
        $em->flush();
        // start Unix
        if (CmdHandler::isUnix()) {
            $cmd  = $this->getSchedulerCmd($scheduler);
            $pids = CmdHandler::getProcess($cmd);
            foreach ($pids as $pid) {
                CmdHandler::killProcess($this->container, $pid);
                $this->get('session')->getFlashBag()
                    ->set('success', 'Scheduler "' . $scheduler->getName() . '" has been stoped.');
            }
        }
        return $this->redirect($this->generateUrl('mapbender_workflow_scheduler_index'));
    }

    private function getSchedulerCmd(Scheduler $scheduler, $full = false)
    {
        $cmdOpts = "mapbender:workflow:scheduler " . $scheduler->getName() . " schedulercontroller true --env=workflow";
        if ($full) {
            return CmdHandler::generateFullCmd(CmdHandler::generateMinCmd($this->container, $cmdOpts), 'null');
        } else {
            return CmdHandler::generateMinCmd($this->container, $cmdOpts);
        }
    }
}
