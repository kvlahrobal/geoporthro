<?php

namespace Mapbender\WorkflowBundle\Extension;

use Mapbender\WorkflowBundle\Entity\Task;
use Mapbender\WorkflowBundle\Entity\TaskReport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ElementExtension
 */
class TaskExtension extends \Twig_Extension
{

    /**
     *
     * @var type
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_task';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('taskhandler', array($this, 'getTaskHandler')),
            new \Twig_SimpleFilter('targetFromReport', array($this, 'getReportTarget')),
        );
    }

    public function getTaskHandler(Task $task)
    {
        $class = $task->getClass();
        $handler = new $class($this->container, $task);
        return $handler;
    }

    public function getReportTarget(TaskReport $taskreport)
    {
        $handler = $this->getTaskHandler($taskreport->getTask());
        return $handler->targetFromReport($taskreport);
    }
}
