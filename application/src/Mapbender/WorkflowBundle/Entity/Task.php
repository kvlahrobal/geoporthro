<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\WorkflowBundle\Entity\Workflow;

/**
 * Definition of Task
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_workflow_task")
 */
class Task
{
    /**
     * @var integer a task id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string a task title
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $class;

    /**
     * @var Workflow a task's workflow
     * @ORM\ManyToOne(targetEntity="Workflow",inversedBy="tasks")
     * @ORM\JoinColumn(name="workflow_id", referencedColumnName="id", nullable=true)
     */
    protected $workflow;

    /**
     * @var string a task title
     * @ORM\Column(type="string", nullable=false)
     */
    protected $title;

    /**
     * @var Collection A list of task handler options
     * @ORM\OneToMany(targetEntity="\Mapbender\WorkflowBundle\Entity\TaskOption",
     * mappedBy="task", cascade={"persist","remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $options;

    /**
     * @var ArrayCollection a list of reports
     * @ORM\OneToMany(targetEntity="\Mapbender\WorkflowBundle\Entity\TaskReport",
     * mappedBy="task", cascade={"persist","remove", "merge"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $reports;

    /**
     * @var integer a task proiority
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $priority;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
        $this->reports = new \Doctrine\Common\Collections\ArrayCollection();
//        $this->priority = 0;
    }

    /**
     * Sets id
     * @param integer $id id
     * @return \Mapbender\WorkflowBundle\Entity\Task
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set class
     *
     * @param string $class
     * @return Task
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Task
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return Task
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set workflow
     *
     * @param \Mapbender\WorkflowBundle\Entity\Workflow $workflow
     * @return Task
     */
    public function setWorkflow(\Mapbender\WorkflowBundle\Entity\Workflow $workflow = null)
    {
        $this->workflow = $workflow;

        return $this;
    }

    /**
     * Get workflow
     *
     * @return \Mapbender\WorkflowBundle\Entity\Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * Add options
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $options
     * @return Task
     */
    public function addOption(\Mapbender\WorkflowBundle\Entity\TaskOption $options)
    {
        $this->options[] = $options;

        return $this;
    }

    /**
     * Remove options
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $options
     */
    public function removeOption(\Mapbender\WorkflowBundle\Entity\TaskOption $options)
    {
        $this->options->removeElement($options);
    }

    /**
     * Get options
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Add report
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $report
     * @return Task
     */
    public function addReport(\Mapbender\WorkflowBundle\Entity\TaskOption $report)
    {
        $this->reports[] = $report;

        return $this;
    }

    /**
     * Remove report
     *
     * @param \Mapbender\WorkflowBundle\Entity\TaskOption $report
     */
    public function removeReport(\Mapbender\WorkflowBundle\Entity\TaskReport $report)
    {
        $this->reports->removeElement($report);
    }

    /**
     * Get reports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReports()
    {
        return $this->reports;
    }

    public function __toString()
    {
        return (string) $this->getId();
    }
}
