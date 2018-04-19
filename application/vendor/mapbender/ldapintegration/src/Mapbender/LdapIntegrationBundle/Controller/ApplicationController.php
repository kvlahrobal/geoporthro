<?php

  namespace Mapbender\LdapIntegrationBundle\Controller;

  use Mapbender\ManagerBundle\Controller\ApplicationController as BaseApplicationController;
  use FOM\ManagerBundle\Configuration\Route;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class ApplicationController extends BaseApplicationController
{
    /**
     * Shows form for creating new applications
     *
     * @Route("/application/new", name="mapbender_manager_application_new")
     * @Method("GET")
     * @Template("MapbenderLdapIntegrationBundle:Application:new.html.twig")
     */
    public function newAction()
    {
        return parent::newAction();
    }

    /**
     * Edit application
     *
     * @Route("/application/{slug}/edit", requirements = { "slug" = "[\w-]+" }, name="mapbender_manager_application_edit")
     * @Method("GET")
     * @Template("MapbenderLdapIntegrationBundle:Application:edit.html.twig")
     */
    public function editAction($slug)
    {
        return parent::editAction($slug);
    }
}
