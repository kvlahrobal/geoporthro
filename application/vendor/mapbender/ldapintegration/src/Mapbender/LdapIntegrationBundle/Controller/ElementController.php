<?php

namespace Mapbender\LdapIntegrationBundle\Controller;


  use Mapbender\ManagerBundle\Controller\ElementController as BaseElementController;
  use FOM\ManagerBundle\Configuration\Route;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class ElementController extends BaseElementController
{
    /**
     * Replaced security action
     *
     * @Route("/application/{slug}/element/{id}/security", requirements={"id" = "\d+"})
     * @Method("GET")
     * @Template("MapbenderAlkisBundle:Element:security.html.twig")
     */
    public function securityAction($slug, $id)
    {
        return parent::securityAction($slug, $id);
    }
}
