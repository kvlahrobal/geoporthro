<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of TaskOption
 *
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_workflow_taskoption")
 */
class TaskOption
{
    /**
     * @var integer a task id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string a option name
     * @ORM\Column(type="string", length=128)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="\Mapbender\WorkflowBundle\Entity\Task",inversedBy="options")
     * @ORM\JoinColumn(name="task_id", referencedColumnName="id")
     */
    protected $task;

    /**
     * @var string a option name
     * @ORM\Column(type="object", nullable=true)
     */
    protected $value;

    /**
     * @ORM\ManyToOne(targetEntity="\Mapbender\WorkflowBundle\Entity\TaskOption",inversedBy="options")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="\Mapbender\WorkflowBundle\Entity\TaskOption",mappedBy="parent")
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $children;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TaskOption
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param \stdClass $value
     * @return TaskOption
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return \stdClass
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set handler
     *
     * @param \Mapbender\WorkflowBundle\Entity\Task $handler
     * @return TaskOption
     */
    public function setHandler(\Mapbender\WorkflowBundle\Entity\Task $handler = null)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Get handler
     *
     * @return \Mapbender\WorkflowBundle\Entity\Task
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set parent
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $parent
     * @return TaskOption
     */
    public function setParent(\Mapbender\WorkflowBundle\Entity\TaskOption $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Mapbender\WorkflowBundle\Entity\TaskOption
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $children
     * @return TaskOption
     */
    public function addChild(\Mapbender\WorkflowBundle\Entity\TaskOption $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $children
     */
    public function removeChild(\Mapbender\WorkflowBundle\Entity\TaskOption $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set task
     *
     * @param \Mapbender\WorkflowBundle\Entity\Task $task
     * @return TaskOption
     */
    public function setTask(\Mapbender\WorkflowBundle\Entity\Task $task = null)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return \Mapbender\WorkflowBundle\Entity\Task
     */
    public function getTask()
    {
        return $this->task;
    }
}
