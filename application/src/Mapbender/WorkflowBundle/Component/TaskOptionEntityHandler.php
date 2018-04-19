<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WorkflowBundle\Entity\Task;
use Mapbender\WorkflowBundle\Entity\TaskOption;

/**
 * Description of OptionsableHandler
 *
 * @author Paul Schmidt
 */
class TaskOptionEntityHandler extends EntityHandler
{

    public static function fromArray(Task $task, array $options, TaskOption $parent = null)
    {
        if (!ArrayUtil::isAssoc($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            $task_opt = new TaskOption();
            $task_opt->setName($name)->setParent($parent);
            if ($value && is_array($value) && ArrayUtil::isAssoc($value)) {
                if ($child = self::fromArray($task, $value, $task_opt)) {
                    $task_opt->addChild($child);
                }
            } else {
                $task_opt->setValue($value);
            }
            $task->addOption($task_opt->setTask($task));
        }
    }
//
//    public function initTaskOption($name, $task, $value = null, TaskOption $parent = null, $children = array())
//    {
//        $this->entity->setName($name)->setValue($name)->setParent($parent);
//        foreach ($children as $child) {
//            $this->entity->addChild($child);
//        }
//    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        foreach ($this->entity->getChildren() as $child) {
            self::createHandler($this->container, $child)->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getChildren() as $child) {
            self::createHandler($this->container, $child)->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
//        $this->container->get('doctrine')->getManager()->flush();
    }
}
