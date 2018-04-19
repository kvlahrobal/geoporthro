<?php

namespace Mapbender\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Mapbender\WorkflowBundle\Entity\Workflow;

/**
 * Definition of Scheduler
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_workflow_scheduler")
 */
class Scheduler
{
    const STATUS_NEW     = "NEW";
    const STATUS_SUCCESS = "SUCCESS";
    const STATUS_RUNNING = "RUNNING";
    const STATUS_WAITING = "WAITING";
    const STATUS_ERROR   = "ERROR";
    const STATUS_ABORTED = "ABORTED";

    const INTERVAL_0 = 0;
    const INTERVAL_120 = 120;
    const INTERVAL_3600 = 3600;
    const INTERVAL_HOURLY = self::INTERVAL_3600;
    const INTERVAL_86400 = 86400;
    const INTERVAL_DAILY = self::INTERVAL_86400;

    /**
     * @var integer a scheduler id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string a task name
     * @ORM\Column(type="string", length=128, unique=true)
     * @Assert\Regex(
     *     pattern="/^[0-9\-\_a-zA-Z]+$/",
     *     message="The scheduler name is wrong."
     * )
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string a scheduler title
     * @ORM\Column(type="string", nullable=false)
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $starttime;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $interval = 0;

    /**
     * @var string a scheduler status
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $status = self::STATUS_NEW;


    /**
     *
     * @var Workflow
     * @ORM\OneToOne(targetEntity="Workflow")
     * @ORM\JoinColumn(name="workflow_id", referencedColumnName="id")
     */
    protected $workflow;

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
     * @return Scheduler
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
     * Set title
     *
     * @param string $title
     * @return Scheduler
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
     * Set description
     *
     * @param string $description
     * @return Scheduler
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set starttime
     *
     * @param \DateTime $starttime
     * @return Scheduler
     */
    public function setStarttime($starttime)
    {
        $this->starttime = $starttime;

        return $this;
    }

    /**
     * Get starttime
     *
     * @return \DateTime
     */
    public function getStarttime()
    {
        return $this->starttime;
    }

    /**
     * Set interval
     *
     * @param integer $interval
     * @return Scheduler
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get interval
     *
     * @return integer
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Scheduler
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set workflow
     *
     * @param Workflow $workflow
     * @return Scheduler
     */
    public function setWorkflow(Workflow $workflow = null)
    {
        $this->workflow = $workflow;

        return $this;
    }

    /**
     * Get workflow
     *
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }
}
