<?php

namespace FOM\ManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use FOM\ManagerBundle\DependencyInjection\Compiler\RouteAnnotationsPass;

/**
 * FoMManagerBundle - provides Manager interface infrastructure for other bundles.
 *
 * @author Christian Wygoda
 */
class FOMManagerBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RouteAnnotationsPass());
    }
}

