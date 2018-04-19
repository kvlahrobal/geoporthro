<?php

namespace Mapbender\WorkflowBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\WorkflowBundle\Component\TaskOptionEntityHandler;
use Mapbender\WorkflowBundle\Entity\Task;
use Mapbender\WorkflowBundle\Form\Type\TaskType;
use Mapbender\WorkflowBundle\MapbenderWorkflowBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Mapbender Task management
 *
 * @author Paul Schmidt
 */
class TaskController extends Controller
{

    /**
     * Show task class selection
     *
     * @ManagerRoute("/workflow/{wid}/task/select")
     * @Method({"GET","POST"})
     * @Template
     */
    public function selectAction($wid)
    {
        $workflow = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Workflow")->find($wid);
        if (!$this->get('security.context')->isGranted('CREATE', new Task())) {
            throw new AccessDeniedException();
        }

        $trans         = $this->container->get('translator');
        $taskClasNames = MapbenderWorkflowBundle::getTaskHandler();
        foreach ($taskClasNames as $taskClassName) {
            $title = $trans->trans($taskClassName::getClassTitle());
            $tags  = array();
            foreach ($taskClassName::getClassTags() as $tag) {
                $tags[] = $trans->trans($tag);
            }
            $tasks[$title] = array(
                'class' => $taskClassName,
                'title' => $title,
                'description' => $trans->trans($taskClassName::getClassDescription()),
                'tags' => $tags);
        }
        ksort($tasks, SORT_LOCALE_STRING);
        return array(
            'workflow' => $workflow,
            'tasks' => $tasks);
    }

    /**
     * Shows form for creating new task
     *
     * @ManagerRoute("/workflow/{wid}/task/new/{class}")
     * @Method("GET")
     * @Template("MapbenderWorkflowBundle:Task:edit.html.twig")
     */
    public function newAction($wid, $class)
    {
        if (!class_exists($class)) {
            throw new \RuntimeException('An Task class "' . $class . '" does not exist.');
        }
        $task = new Task();
        if (!$this->get('security.context')->isGranted('CREATE', $task)) {
            throw new AccessDeniedException();
        }
        $task->setClass($class);
        $handler = new $class($this->container, $task);

        TaskOptionEntityHandler::fromArray($task, $handler->getDefaults(), null);
        $workflow = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Workflow")->find($wid);
        $task->setWorkflow($workflow);
        $form     = $this->container->get("form.factory")->create(new TaskType(), $task);
        return array(
            'form' => $form->createView(),
            'task' => $task
        );
    }

    /**
     * Create a new task from POSTed data
     *
     * @ManagerRoute("/workflow/{wid}/task/new")
     * @Method("POST")
     * @Template("MapbenderManagerBundle:Element:edit.html.twig")
     */
    public function createAction($wid)
    {
        $taskForm = $this->get('request')->get('task');
        if (!$taskForm || !isset($taskForm['class'])) {
            throw new \RuntimeException('A Task class does not exist.');
        }
        $task = new Task();
        if (!$this->get('security.context')->isGranted('CREATE', $task)) {
            throw new AccessDeniedException();
        }
        $task->setClass($taskForm['class']);
        $handler  = new $taskForm['class']($this->container, $task);
        TaskOptionEntityHandler::fromArray($task, $handler->getDefaults(), null);
        $workflow = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Workflow")->find($wid);
        $form     = $this->container->get("form.factory")->create(new TaskType(), $task);
        $form->bind($this->get('request'));
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $task->setWorkflow($workflow);
            $handler->save();
            $em->flush();
            $em->getConnection()->commit();
            $this->get('session')->getFlashBag()->set(
                'success',
                'Your Task "' . $task->getTitle() . '" has been saved.'
            );
            return new Response('', 201);
        } else {
            $task->setWorkflow($workflow);
            return array(
                'form' => $form->createView(),
                'task' => $task
            );
        }
    }

    /**
     * Edit task
     *
     * @ManagerRoute("/workflow/task/{tid}/edit", requirements={"tid" = "\d+"})
     * @Method("GET")
     * @Template
     */
    public function editAction($tid)
    {
        $task = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Task")->find($tid);
        if (!$this->get('security.context')->isGranted('CREATE', $task)) {
            throw new AccessDeniedException();
        }
        $form = $this->container->get("form.factory")->create(new TaskType(), $task);
        return array(
            'form' => $form->createView(),
            'task' => $task
        );
    }

    /**
     * Updates task by POSTed data
     *
     * @ManagerRoute("/workflow/task/{tid}/edit", requirements = {"tid" = "\d+" })
     * @Method("POST")
     * @Template("MapbenderWorkflowBundle:Task:edit.html.twig")
     */
    public function updateAction($tid)
    {
        $task = $this->getDoctrine()->getRepository('MapbenderWorkflowBundle:Task')->find($tid);
        if (!$task) {
            throw $this->createNotFoundException('The task with the id "'
                . $tid . '" does not exist.');
        }
        if (!$this->get('security.context')->isGranted('EDIT', $task)) {
            throw new AccessDeniedException();
        }
        $form = $this->container->get("form.factory")->create(new TaskType(), $task);
        $form->bind($this->get('request'));
        if ($form->isValid()) {
            $em      = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $class   = $task->getClass();
            $handler = new $class($this->container, $task);
            $handler->save();
            $em->flush();
            $em->getConnection()->commit();

            $this->get('session')->getFlashBag()->set('success', 'Your task has been saved.');

            return new Response('', 201);
        } else {
            return array(
                'form' => $form['type']->getForm()->createView(),
                'theme' => $form['theme'],
                'assets' => $form['assets']);
        }
    }

    /**
     * Delete confirmation page
     * @ManagerRoute("/workflow/{wid}/task/{tid}/delete")
     * @Method("GET")
     * @Template("MapbenderWorkflowBundle:Task:delete.html.twig")
     */
    public function confirmDeleteAction($wid, $tid)
    {
        $task = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Task")->find($tid);
        if ($task === null) {
            $this->get('session')->getFlashBag()->set('error', 'Your Task has been already deleted.');
            $workflow = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Workflow")->find($wid);
            return $this->redirect($this->generateUrl(
                'mapbender_workflow_workflow_edit',
                array('wid' => $workflow->getId())
            ));
        }
        if (!$this->get('security.context')->isGranted('DELETE', $task)) {
            throw new AccessDeniedException();
        }
        $form = $this->createFormBuilder(array('id' => $task->getId()))->add('id', 'hidden')->getForm();
        return array(
            'workflow' => $task,
            'form' => $form->createView());
    }

    /**
     * Delete task
     *
     * @ManagerRoute("/workflow/{wid}/task/{tid}/delete")
     * @Method("POST")
     */
    public function deleteAction($wid, $tid)
    {
        $task = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Task")->find($tid);
        if (!$task) {
            $this->get('session')->getFlashBag()->set('error', 'Your Task has been already deleted.');
            $workflow = $this->getDoctrine()->getRepository("MapbenderWorkflowBundle:Workflow")->find($wid);
            return $this->redirect($this->generateUrl(
                'mapbender_workflow_workflow_edit',
                array('wid' => $workflow->getId())
            ));
        }
        if (!$this->get('security.context')->isGranted('DELETE', $task)) {
            throw new AccessDeniedException();
        }
        try {
            $aclProvider = $this->get('security.acl.provider');
            $em          = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $oid         = ObjectIdentity::fromDomainObject($task);
            $aclProvider->deleteAcl($oid);
            $class       = $task->getClass();
            $handler     = new $class($this->container, $task);
            $handler->remove();
            $em->flush();
            $em->commit();
            $this->get('session')->getFlashBag()->set('success', 'Your task has been deleted.');
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->set('error', 'Your task couldn\'t be deleted.');
        }
        return new Response();
    }
}
