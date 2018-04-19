<?php
/**
 * TODO: License
 */

namespace Mapbender\WorkflowBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of WorkflowCommand
 * @author Paul Schmidt
 */
class WorkflowCommand extends CommonCommand
{

    protected function configure()
    {
        $this
            ->setName('mapbender:workflow:workflow')
            ->setDescription('Starts the workflow with a given name.')
            ->addArgument('name', InputArgument::REQUIRED, 'Workflow name')
            ->addArgument('caller', InputArgument::REQUIRED, 'caller')
            ->addArgument('log', InputArgument::REQUIRED, 'log');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->gcEnable();
        $workflowName = $input->getArgument('name');
        $log          = $input->getArgument('log');
        $output->writeln('Starts a workflow with name:"' . $workflowName . '".');
        $em           = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $workflow     = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Workflow')
            ->findOneByName($workflowName);
        $this->checkEntity($em, $workflow, !$workflow, 'Workflow with name "' . $workflowName . '" can\'t be found.');
        $this->resetEntityManager($em, $workflow);
        # run workflow
        $em->getConnection()->beginTransaction();
        $this->start($workflowName, $output, $log ? true : false);
        $em->flush();
        $em->getConnection()->commit();
        $this->resetEntityManager($em);
    }

    public function start($workflowName, OutputInterface $output, $log = true)
    {
        $output->writeln('Run workflow:"' . $workflowName . '".');
        $em       = $this->getContainer()->get("doctrine")->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $workflow = $this->getContainer()->get("doctrine")
            ->getRepository('Mapbender\WorkflowBundle\Entity\Workflow')
            ->findOneByName($workflowName);
        $this->checkEntity($em, $workflow, !$workflow, 'Workflow with name "' . $workflowName . '" can\'t be found.');
        foreach ($workflow->getTasks() as $task) {
            $taskcommand = new TaskCommand("TASKCOMMAND");
            $taskcommand->setContainer($this->getContainer());
            $taskcommand->start($task->getId(), $output, $log);
        }
    }
}
