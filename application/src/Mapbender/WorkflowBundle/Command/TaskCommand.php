<?php
/**
 * TODO: License
 */

namespace Mapbender\WorkflowBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of TaskCommand
 * @author Paul Schmidt
 */
class TaskCommand extends CommonCommand
{

    protected function configure()
    {
        $this
            ->setName('mapbender:workflow:task')
            ->setDescription('Starts the task with a given name.')
            ->addArgument('id', InputArgument::REQUIRED, 'task id')
            ->addArgument('caller', InputArgument::REQUIRED, 'caller')
            ->addArgument('log', InputArgument::REQUIRED, 'log');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->gcEnable();
        $taskId = $input->getArgument('id');
        $log = $input->getArgument('log');
        $output->writeln('Starts a task with id:"' . $taskId . '".');
        $em            = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $task     = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Task')
            ->find($taskId);
        $this->checkEntity($em, $task, !$task, 'Task with id: "' . $taskId . '" can\'t be found.');
        $this->resetEntityManager($em, $task);
        # run task
        $em->getConnection()->beginTransaction();
        $this->start($taskId, $output, $log ? true : false);
        $em->flush();
        $em->getConnection()->commit();
        $this->resetEntityManager($em);
    }

    public function start($taskId, OutputInterface $output, $log = true)
    {
        $output->writeln('Run task:"' . $taskId . '".');
        $em   = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $task = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Task')
            ->find($taskId);
        $this->checkEntity($em, $task, !$task, 'Task with id: "' . $taskId . '" can\'t be found.');
        $class = $task->getClass();
        $taskRunner = new $class($this->getContainer(), $task);
        $taskRunner->run();
    }
}
