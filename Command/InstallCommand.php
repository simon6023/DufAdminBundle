<?php
namespace Duf\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class InstallCommand extends ContainerAwareCommand
{
    protected $container;
    protected $em;

    protected $admin_username;
    protected $admin_password;

    public function __construct()
    {
        parent::__construct();

        $this->admin_username   = 'test_admin';
        $this->admin_password   = 'test_admin';
        $this->admin_email      = 'test_admin@domain.com';
        $this->admin_firstname  = 'test_admin';
        $this->admin_lastname   = 'test_admin';
    }

    protected function configure()
    {
        $this
            ->setName('dufadmin:install')
            ->setDescription('Initialize DufAdminBundle')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container        = $this->getContainer();
        $this->em               = $this->container->get('doctrine.orm.entity_manager');

        $output->writeln([
                'Create User Roles',
                '============',
            ]);

        $this->createUserRoles();

        $output->writeln([
                'Create Admin User',
                'Username : ' . $this->admin_username,
                'Password : ' . $this->admin_password,
                '============',
            ]);

        $this->createAdminUser();

        $output->writeln([
                'Install assets',
                $this->assetsInstall(),
                '============',
            ]);

        $output->writeln([
                'Dump Assetic assets',
                $this->asseticDump(),
                '============',
            ]);
    }

    private function createUserRoles()
    {
        $roles  = array('TEST_ROLE_ADMIN', 'TEST_ROLE_USER');
        foreach ($roles as $role_name) {
            $role = new \Duf\AdminBundle\Entity\UserRole();
            $role->setName($role_name);

            $this->em->persist($role);
        }

        $this->em->flush();
    }

    private function createAdminUser()
    {
        $role       = $this->em->getRepository('DufAdminBundle:UserRole')->findOneByName('TEST_ROLE_ADMIN');
        $user_infos = array(
                'username'          => $this->admin_username,
                'firstname'         => $this->admin_firstname,
                'lastname'          => $this->admin_lastname,
                'email'             => $this->admin_email,
                'password'          => $this->admin_password,
                'salt'              => uniqid(),
            );

        $user       = new \Duf\AdminBundle\Entity\User();
        $encoder    = $this->container->get('security.encoder_factory')->getEncoder($user);
        $password   = $encoder->encodePassword($user_infos['password'], $user_infos['salt']);

        $user->setUsername($user_infos['username']);
        $user->setFirstname($user_infos['firstname']);
        $user->setLastname($user_infos['lastname']);
        $user->setEmail($user_infos['email']);
        $user->setPassword($password);
        $user->setSalt($user_infos['salt']);
        $user->addRole($role);
        $user->setCreatedAt(new \DateTime());
        $user->setIsActive(true);
        $user->setOptinMessages(true);

        $this->em->persist($user);
        $this->em->flush();
    }

    private function assetsInstall()
    {
        $output         = $this->getCliOutput();
        $application    = $this->getCliApplication();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'assets:install',
                                    )
                                );
        $application->run($input, $output);

        return $output->fetch();
    }

    private function asseticDump()
    {
        $output         = $this->getCliOutput();
        $application    = $this->getCliApplication();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'assetic:dump',
                                    )
                                );
        $application->run($input, $output);

        return $output->fetch();
    }

    private function getCliApplication()
    {
        $kernel         = $this->container->get('kernel');
        $application    = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }

    private function getCliOutput()
    {
        return new BufferedOutput();
    }
}