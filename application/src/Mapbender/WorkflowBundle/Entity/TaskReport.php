<?php

namespace Mapbender\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of TaskReport
 *
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_workflow_taskreport")
 */
class TaskReport
{
    const STATUS_SUCCESS     = "SUCCESS";
    const STATUS_TIMEOUT     = "TIMEOUT";
    const STATUS_FAIL        = "FAIL";
    const STATUS_EXCEPTION   = "EXCEPTION";
    const STATUS_ERROR       = "ERROR";
    const STATUS_EXCEPT_TIME = "EXCEPT TIME";
    const STATUS_DISABLED    = "DISABLED";

    /**
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
     * @ORM\Column(type="float", nullable=true)
     */
    protected $latency = 0.0;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $stability = 0.0;

    /**
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $count = 0;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Task", inversedBy="reports", cascade={"refresh"})
     */
    protected $task;

    /**
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $ident;

    /**
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $status;

    /**
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $action;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    public function __construct()
    {
        $this->starttime = new \DateTime();
//        $this->status    = self::STATUS_FAIL;
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
     */
    public function setStarttime(\DateTime $starttime)
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
     * Set latency
     *
     * @param float $latency
     */
    public function setLatency($latency)
    {
        $this->latency = $latency;
        return $this;
    }

    /**
     * Get latency
     *
     * @return float
     */
    public function getLatency()
    {
        return $this->latency;
    }

    /**
     * Set stability
     *
     * @param float $stability
     */
    public function setStability($stability)
    {
        $this->stability = $stability;
        return $this;
    }

    /**
     * Get stability
     *
     * @return float
     */
    public function getStability()
    {
        return $this->stability;
    }

    /**
     * Set count
     *
     * @param integer $count
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Get changed
     *
     * @return boolean
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set task
     *
     * @param Mapbender\WorkflowBundle\Entity\Task $task
     */
    public function setTask(Task $task)
    {
        $this->task = $task;
        return $this;
    }

    /**
     * Get task
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set ident
     *
     * @param string $ident
     */
    public function setIdent($ident)
    {
        $this->ident = $ident;
        return $this;
    }

    /**
     * Get ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }

    /**
     * Set status
     *
     * @param string $status
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
     * Set action
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set message
     *
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
