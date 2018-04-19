<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;

/**
 * Description of ScedulerEntityHandler
 *
 * @author Pau Schmidt
 */
class ScedulerEntityHandler extends EntityHandler implements Reportable
{
    public function getReport()
    {
        $wfh = self::createHandler($this->container, $this->entity->getWorkflow());
        $wfh->getReport();
    }
}
