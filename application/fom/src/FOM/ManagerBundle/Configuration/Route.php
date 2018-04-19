<?php

namespace FOM\ManagerBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route as BaseRoute;

/**
 * Route annotation for Manager Controllers.
 *
 * This is just an subclass of the FrameworkExtraBundle's route annotation.
 * All the magic with route prefixing happens in FOM\ManagerBundle\Routing\AnnotatedRouteControllerLoader.
 *
 * @Annotation
 * @author Christian Wygoda
 */
class Route extends BaseRoute
{
}

