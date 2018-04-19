<?php

namespace FOM\UserBundle\Command;

use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\UserHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;

/**
 * Reset root account.
 *
 * @author Christian Wygoda
 */
class ResetRootAccountCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('username', '', InputOption::VALUE_REQUIRED, 'The username to use for the root account'),
                new InputOption('email', '', InputOption::VALUE_REQUIRED, 'The e-mail address for the root account'),
                new InputOption('password', '', InputOption::VALUE_REQUIRED, 'The password to set for the root account'),
                new InputOption('silent', '', InputOption::VALUE_NONE, 'Perform a silent reset')))
            ->setDescription('Resets the root account')
            ->setHelp(<<<EOT
The <info>fom:user:resetroot</info> command can be used to create or update
the root user account. This account is identified by id 1, username, e-mail
and password can be set.
EOT
            )
            ->setName('fom:user:resetroot');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $root = $this->getRoot();

        if($root === null) {
            foreach(array('username', 'email', 'password') as $option) {
                if($input->getOption($option) === null) {
                    throw new \RuntimeException(
                        sprintf('The %s option must be provided.', $option));
                }
            }
        }

        $action = ($root ? 'reset' : 'creation');
        if($input->isInteractive() && !$input->getOption('silent')) {
            if(!$dialog->askConfirmation($output, $dialog->getQuestion(
                'Do you confirm ' . $action, 'yes', '?'), true)) {
                return 1;
            }
        }

        if(!$root) {
            $root = new User();
            $root->setId(1);
        }

        if($input->getOption('username') !== null) {
            //TODO: Validate, use same validator as in the askAndValidate below
            $root->setUsername($input->getOption('username'));
        }

        if($input->getOption('email') !== null) {
            //TODO: Validate, use same validator as in the askAndValidate below
            $root->setEmail($input->getOption('email'));
        }

        if($input->getOption('email') !== null) {
            //TODO: Validate, use same validator as in the askAndValidate below
            $helper = new UserHelper($this->getContainer());
            $helper->setPassword($root, $input->getOption('password'));
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($root);
        $em->flush();

        $output->writeln(array(
            '',
            'The root is now usable. Have fun!',
            ''));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $root = $this->getRoot();
        $silent = $input->getOption('silent');

        $dialog->writeSection($output, 'Welcome to the Mapbender3 root account management command');


        if(!$silent || $input->getOption('username') === null) {
            $output->writeln(array(
                '',
                'Enter the username to use for the root account.',
                ''));

            // @TODO: Validate (askAndValidate())
            $username = ($root ? $root->getUsername() : $input->getOption('username'));
            $username = $dialog->ask($output, $dialog->getQuestion('Username', $username), $username);
            $input->setOption('username', $username);
        }


        if(!$silent || $input->getOption('email') === null) {
            $output->writeln(array(
                '',
                'Enter the e-mail adress to use for the root account.',
                ''));

            // @TODO: Validate (askAndValidate())
            $email = ($root ? $root->getEmail() : '');
            $email = $dialog->ask($output, $dialog->getQuestion('E-Mail', $email), $email);
            $input->setOption('email', $email);
        }


        if(!$silent || $input->getOption('password') === null) {
            $output->writeln(array(
                '',
                'Enter the password to use for the root account.',
                ''));

            // @TODO: Validate (askAndValidate())
            $password = $dialog->ask($output, $dialog->getQuestion('Password', ''), '');
            $input->setOption('password', $password);
        }
    }

    protected function getRoot()
    {
        $root = $this->getContainer()->get('doctrine')
            ->getRepository('FOMUserBundle:User')
            ->find(1);

        return $root;
    }

    protected function getDialogHelper()
    {
        $dialog = $this->getHelperSet()->get('dialog');
        if(!$dialog || get_class($dialog) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper') {
            $this->getHelperSet()->set($dialog = new DialogHelper());
        }

        return $dialog;
    }
}
