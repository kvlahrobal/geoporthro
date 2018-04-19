<?php

namespace Mapbender\WorkflowBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class MapbenderWorkflowBundle extends MapbenderBundle
{

    public function getManagerControllers()
    {
        $trans = $this->container->get('translator');
        $prefix = 'mapbender.workflowbundle.mapbenderworkflowbundle';
        return array(
            array(
                'weight' => 20,
                'title' => $trans->trans($prefix . ".components"),
                'route' => 'mapbender_workflow_workflow_index',
                'routes' => array(
                    'mapbender_workflow_workflow',
                ),
                'subroutes' => array(
                    0 => array('title' => $trans->trans($prefix . ".workflows"),
                        'route' => 'mapbender_workflow_workflow_index',
                        'subroutes' => array(
                            0 => array(
                                'title' => $trans->trans($prefix . ".new_workflow"),
                                'route' => 'mapbender_workflow_workflow_new',
                                'enabled' => function ($securityContext) {
                                    $oid = new ObjectIdentity('class', 'Mapbender\WorkflowBundle\Entity\Workflow');
                                    return $securityContext->isGranted('CREATE', $oid);
                                }
                            )
                        )
                    ),
                    1 => array('title' => $trans->trans($prefix . ".monitoring"),
                        'route' => 'mapbender_workflow_scheduler_index',
                        'subroutes' => array(
                            0 => array(
                                'title' => $trans->trans($prefix . ".new_scheduler"),
                                'route' => 'mapbender_workflow_scheduler_new',
                                'enabled' => function ($securityContext) {
                                    $oid = new ObjectIdentity('class', 'Mapbender\WorkflowBundle\Entity\Workflow');
                                    return $securityContext->isGranted('CREATE', $oid);
                                }
                            )
                        )
                    ),
                )
            ),
        );
    }

    public static function getTaskHandler()
    {
        return array(
            'Mapbender\WorkflowBundle\Component\SourcePing'
        );
    }
}
