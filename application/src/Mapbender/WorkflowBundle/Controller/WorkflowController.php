<?php
/**
 * Mapbender workflow management
 *
 * @author Paul Schmidt
 */

namespace Mapbender\WorkflowBundle\Controller;

use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\WorkflowBundle\Entity\Workflow;
use Mapbender\WorkflowBundle\Form\Type\WorkflowType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class WorkflowController extends Controller
{

    /**
     * Render a list of Workflows.
     *
     * @ManagerRoute("/workflows")
     * @Method("GET")
     * @Template
     */
    public function indexAction()
    {
        $secCntx   = $this->get('security.context');
        $workflows = $this->getDoctrine()->getRepository("Mapbender\WorkflowBundle\Entity\Workflow")->findAll();
        $allowed   = array();
        foreach ($workflows as $workflow) {
            if ($secCntx->isGranted('VIEW', $workflow)) {
                if (!$secCntx->isGranted('OWNER', $workflow)) {
                    continue;
                }
                $allowed[] = $workflow;
            }
        }
        return array('workflows' => $allowed);
    }

    /**
     * Shows form for creating new workflow
     *
     * @ManagerRoute("/workflow/new")
     * @Method("GET")
     * @Template
     */
    public function newAction()
    {
        $workflow = new Workflow();
        if (!$this->get('security.context')->isGranted('CREATE', $workflow)) {
            throw new AccessDeniedException();
        }
        $form = $this->container->get("form.factory")->create(new WorkflowType(), $workflow);
        return array(
            'application' => $workflow,
            'form' => $form->createView(),
            'form_name' => $form->getName()
        );
    }

    /**
     * Create a new workflow from POSTed data
     *
     * @ManagerRoute("/workflow")
     * @Method("POST")
     * @Template("MapbenderWorkflowBundle:Workflow:new.html.twig")
     */
    public function createAction()
    {
        $workflow = new Workflow();
        if (!$this->get('security.context')->isGranted('CREATE', $workflow)) {
            throw new AccessDeniedException();
        }

        $form    = $this->container->get("form.factory")->create(new WorkflowType(), $workflow);
        $request = $this->getRequest();

        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $em->persist($workflow);
            $em->flush();
//            $aclManager = $this->get('fom.acl.manager');
//            $aclManager->setObjectACLFromForm($workflow, $form->get('acl'), 'object');
//            $em->persist($workflow);
//            $em->flush();
            $em->getConnection()->commit();
            return $this->redirect($this->generateUrl('mapbender_workflow_workflow_index'));
        }
        return array(
            'application' => $workflow,
            'form' => $form->createView(),
            'form_name' => $form->getName()
        );
    }

    /**
     * Edit workflow
     *
     * @ManagerRoute("/workflow/{wid}/edit")
     * @Method("GET")
     * @Template
     */
    public function editAction($wid)
    {
        $workflow = $this->getDoctrine()->getRepository("Mapbender\WorkflowBundle\Entity\Workflow")->find($wid);
        if (!$this->get('security.context')->isGranted('EDIT', $workflow)) {
            throw new AccessDeniedException();
        }
        $form = $this->container->get("form.factory")->create(new WorkflowType(), $workflow);
        return array(
            'workflow' => $workflow,
            'form' => $form->createView(),
            'form_name' => $form->getName());
    }

    /**
     * Updates workflow by POSTed data
     *
     * @ManagerRoute("/application/{wid}/update")
     * @Method("POST")
     * @Template("MapbenderWorkflowBundle:Workflow:edit.html.twig")
     */
    public function updateAction($wid)
    {
        $workflow = $this->getDoctrine()->getRepository("Mapbender\WorkflowBundle\Entity\Workflow")->find($wid);
        if (!$this->get('security.context')->isGranted('EDIT', $workflow)) {
            throw new AccessDeniedException();
        }
        $form    = $this->container->get("form.factory")->create(new WorkflowType(), $workflow);
        $request = $this->getRequest();
        $form->bind($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->beginTransaction();
            $em->persist($workflow);
            $em->flush();
            $em->getConnection()->commit();
            return $this->redirect($this->generateUrl(
                'mapbender_workflow_workflow_edit',
                array('wid' => $workflow->getId())
            ));
        } else {
            return array(
                'workflow' => $workflow,
                'form' => $form->createView(),
                'form_name' => $form->getName());
        }
    }

    /**
     * Delete confirmation page
     * @ManagerRoute("/workflow/{wid}/delete")
     * @Method("GET")
     * @Template("MapbenderWorkflowBundle:Workflow:delete.html.twig")
     */
    public function confirmDeleteAction($wid)
    {
        $workflow = $this->getDoctrine()->getRepository("Mapbender\WorkflowBundle\Entity\Workflow")->find($wid);
        if ($workflow === null) {
            $this->get('session')->getFlashBag()->set('error', 'Your Workflow has been already deleted.');
            return $this->redirect($this->generateUrl('mapbender_workflow_workflow_index'));
        }
        if (!$this->get('security.context')->isGranted('DELETE', $workflow)) {
            throw new AccessDeniedException();
        }
        $form = $this->createFormBuilder(array('id' => $workflow->getId()))->add('id', 'hidden')->getForm();
        return array(
            'workflow' => $workflow,
            'form' => $form->createView());
    }

    /**
     * Delete workflow
     *
     * @ManagerRoute("/workflow/{wid}/delete")
     * @Method("POST")
     */
    public function deleteAction($wid)
    {
        $workflow = $this->getDoctrine()->getRepository("Mapbender\WorkflowBundle\Entity\Workflow")->find($wid);
        if (!$this->get('security.context')->isGranted('DELETE', $workflow)) {
            throw new AccessDeniedException();
        }
        try {
            $em          = $this->getDoctrine()->getManager();
            $aclProvider = $this->get('security.acl.provider');
            $em->getConnection()->beginTransaction();
            $oid         = ObjectIdentity::fromDomainObject($workflow);
            $aclProvider->deleteAcl($oid);
            $em->remove($workflow);
            $em->flush();
            $em->commit();
            $this->get('session')->getFlashBag()->set('success', 'Your workflow has been deleted.');
        } catch (Exception $e) {
            $this->get('session')->getFlashBag()->set('error', 'Your workflow couldn\'t be deleted.');
        }
        return $this->redirect($this->generateUrl('mapbender_workflow_workflow_index'));
    }
}
