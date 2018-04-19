<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Description of CommonCommand
 *
 * @author Paul Schmidt
 */
abstract class CommonCommand extends ContainerAwareCommand
{

    /**
     * Resets entity manager
     * @param EntityManager $em entity manager
     * @param mixed $obj entity object
     */
    protected function resetEntityManager(EntityManager $em, $obj = null)
    {
        if ($obj) {
            $em->detach($obj);
        }
        $em->flush();
        $em->clear();
        if ($obj) {
            unset($obj);
        }
        $this->gcClean();
    }

    /**
     *  Activates the circular reference collector, if not activated.
     */
    protected function gcEnable()
    {
        if (!gc_enabled()) {
            gc_enable();
        }
    }

    /**
     * Forces collection of any existing garbage cycles.
     */
    protected function gcClean()
    {
        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }
    
    /**
     *
     * @param EntityManager $em entity manager
     * @param mixed $object entity object to check
     * @param boolean $expression expression to check
     * @param string $message message for exception
     * @throws \Doctrine\ORM\NoResultException
     */
    protected function checkEntity(EntityManager $em, $object, $expression, $message)
    {
        if ($expression) {
            $this->resetEntityManager($em, $object);
            throw new \Doctrine\ORM\NoResultException($message);
        }
    }
}
