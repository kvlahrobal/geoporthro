<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\WorkflowBundle\Entity\TaskReport;

/**
 * Description of WmsPingEntityHandler
 *
 * @author Paul Schmidt
 */
abstract class TaskEntityHandler extends EntityHandler# implements Reportable
{

    public function getDefaults()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        foreach ($this->entity->getOptions() as $option) {
            $option->setTask($this->entity);
            self::createHandler($this->container, $option)->save();
        }
        $this->container->get('doctrine')->getManager()->persist($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getOptions() as $option) {
            self::createHandler($this->container, $option)->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * Returns class title's key.
     */
    public static function generateClassKey($sufix)
    {
        return strtolower(preg_replace('/\\\|\//', '.', get_called_class()). ".$sufix");
    }

    /**
     * Returns class title's key.
     */
    public static function getClassTitle()
    {
        return self::generateClassKey("title");
    }

    /**
     * Returns class description's key.
     */
    public static function getClassDescription()
    {
        return self::generateClassKey("description");
    }

    /**
     * Returns class tags as key list.
     */
    public static function getClassTags()
    {
        return array();
    }

    /**
     * Returns class tags as key list.
     */
    abstract public function run();

    /**
     * Render the task report.
     *
     * @return string
     */
    abstract public function renderReport();

    /**
     * Returns class tags as key list.
     */
    abstract public function targetFromReport(TaskReport $taskreport);
}
