<?php
/**
 * TODO: License
 */

namespace Mapbender\WorkflowBundle\Command;

use Mapbender\WorkflowBundle\Entity\Scheduler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of SchedulerCommand
 * @author Paul Schmidt
 */
class SchedulerCommand extends CommonCommand
{

    protected function configure()
    {
        $this
            ->setName('mapbender:workflow:scheduler')
            ->setDescription('Starts the scheduler with given name.')
            ->addArgument('name', InputArgument::REQUIRED, 'Scheduler name')
            ->addArgument('caller', InputArgument::REQUIRED, 'caller')
            ->addArgument('log', InputArgument::REQUIRED, 'log');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->gcEnable();
        $schedulerName = $input->getArgument('name');
        $log           = $input->getArgument('log');
        $output->writeln('Starts a scheduler with name:"' . $schedulerName . '".');
        $em            = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $scheduler     = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Scheduler')
            ->findOneByName($schedulerName);
        $this->checkEntity(
            $em,
            $scheduler,
            (!$scheduler || !$scheduler->getWorkflow() || !$scheduler->getWorkflow()->getTasks()->count() === 0),
            'Scheduler with name "' . $schedulerName . '" can\'t be found.'
        );
        $this->gcEnable();

        $now = new \DateTime();
        $wait = $scheduler->getStarttime()->getTimestamp() - $now->getTimestamp();
        if ($wait > 0) {
            $scheduler->setStatus(Scheduler::STATUS_WAITING);
            $em->persist($scheduler);
            $em->flush();
            $this->resetEntityManager($em, $scheduler);
            sleep($wait);
        }

        $rotate        = 1;
        while ($rotate) { # $rotate === 0 -> false, otherwise -> true
            $this->gcEnable();
            $em        = $this->getContainer()->get("doctrine")->getEntityManager();
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $scheduler = $this->getContainer()->get("doctrine")
                ->getRepository('Mapbender\WorkflowBundle\Entity\Scheduler')
                ->findOneByName($schedulerName);
            $this->checkEntity(
                $em,
                $scheduler,
                (!$scheduler || !$scheduler->getWorkflow() || !$scheduler->getWorkflow()->getTasks()->count() === 0),
                'Scheduler with name "' . $schedulerName . '" can\'t be found.'
            );
            $scheduler->setStatus(Scheduler::STATUS_RUNNING);
            $em->persist($scheduler);
            $em->flush();
            # no loop if $scheduler->interval === 0
            $rotate    = $scheduler->getInterval();
            $em->getConnection()->beginTransaction();
            $this->start($schedulerName, $output, $log ? true : false);
            $em->flush();
            $em->getConnection()->commit();
            $scheduler->setStatus(Scheduler::STATUS_WAITING);
            $em->persist($scheduler);
            $em->flush();
            $this->resetEntityManager($em, $scheduler);
            $this->gcEnable();
            sleep($rotate);
        }
        $em        = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $scheduler = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Scheduler')
            ->findOneByName($schedulerName);
        $this->checkEntity(
            $em,
            $scheduler,
            (!$scheduler || !$scheduler->getWorkflow() || !$scheduler->getWorkflow()->getTasks()->count() === 0),
            'Scheduler with name "' . $schedulerName . '" can\'t be found or has no  tasks.'
        );
        $scheduler->setStatus(Scheduler::STATUS_SUCCESS);
        $em->persist($scheduler);
        $em->flush();
        $em->getConnection()->commit();
        $this->resetEntityManager($em, $scheduler);
    }

    public function start($schedulerName, OutputInterface $output, $log = true)
    {
        $output->writeln('Run scheduler:"' . $schedulerName . '".');
        $em              = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $scheduler       = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Scheduler')
            ->findOneByName($schedulerName);
        $this->checkEntity(
            $em,
            $scheduler,
            (!$scheduler || !$scheduler->getWorkflow() || !$scheduler->getWorkflow()->getTasks()->count() === 0),
            'Scheduler with name "' . $schedulerName . '" can\'t be found.'
        );
        $workflowcommand = new WorkflowCommand("WORKFLOWCOMMAND");
        $workflowcommand->setContainer($this->getContainer());
        $workflowcommand->start($scheduler->getWorkflow()->getName(), $output, $log);
    }
}
