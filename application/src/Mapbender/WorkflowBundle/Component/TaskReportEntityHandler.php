<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\WorkflowBundle\Entity\TaskReport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of TaskReportEntityHandler
 *
 * @author Paul Schmidt
 */
class TaskReportEntityHandler extends EntityHandler
{
    protected $start;
    protected $end;

    public function __construct(ContainerInterface $container, $entity)
    {
        parent::__construct($container, $entity);
        $this->entity->setStarttime(new \DateTime());
    }

    public function setStarttime()
    {
        $this->entity->setStarttime(new \DateTime());
    }

    public function setLatency($latency = null)
    {
        if ($latency) {
            $this->entity->setLatency($latency);
        } else {
            $end = new \DateTime();
            $this->entity->setLatency($end->getTimestamp() - $this->entity->getStarttime()->getTimestamp());
        }
    }

    public function setResult($status, $message, $isStatusOk)
    {
        $count = $this->entity->getCount();
        $stab = $this->entity->getStability();
        $countOk = round($stab * $count / 100.0);
        $count++;
        if ($isStatusOk) {
            $countOk++;
        }
        $this->entity->setStability($countOk * 100.0 / $count);
        $this->entity->setStatus($status);
        $this->entity->setCount($count);
        $this->entity->setMessage($message);
    }
}
