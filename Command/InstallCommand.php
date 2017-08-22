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

    protected $user_class;
    protected $user_role_class;

    public function __construct()
    {
        parent::__construct();

        $this->admin_username   = 'admin';
        $this->admin_password   = 'admin';
        $this->admin_email      = 'admin@domain.com';
        $this->admin_firstname  = 'John';
        $this->admin_lastname   = 'Doe';
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

        $user_entity_name       = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('user_entity');
        $user_role_entity_name  = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('user_role_entity');

        $this->user_class       = $this->container->get('duf_admin.dufadminrouting')->getEntityClass($user_entity_name);
        $this->user_role_class  = $this->container->get('duf_admin.dufadminrouting')->getEntityClass($user_role_entity_name);

        $output->writeln([
                'Create Database',
                $this->createDatabase(),
                '============',
            ]);

        $output->writeln([
                'Update Database Schema',
                $this->updateDatabaseSchema(),
                '============',
            ]);

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
                'Configure languages',
                '============',
            ]);

        $this->configureLanguages();

        $output->writeln([
                'Import translations',
                $this->importTranslations(),
                '============',
            ]);

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

        $output->writeln([
                'Clear cache',
                $this->clearCache(),
                '============',
            ]);
    }

    private function createUserRoles()
    {
        $roles  = array('ROLE_ADMIN', 'ROLE_USER');
        foreach ($roles as $role_name) {
            $role = new $this->user_role_class;
            $role->setName($role_name);

            $this->em->persist($role);
        }

        $this->em->flush();
    }

    private function createAdminUser()
    {
        $role       = $this->em->getRepository($this->user_role_class)->findOneByName('ROLE_ADMIN');
        $user_infos = array(
                'username'          => $this->admin_username,
                'firstname'         => $this->admin_firstname,
                'lastname'          => $this->admin_lastname,
                'email'             => $this->admin_email,
                'password'          => $this->admin_password,
                'salt'              => uniqid(),
            );

        $user       = new $this->user_class;
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

    private function configureLanguages()
    {
        $languages = array(
                array(
                    'name'      => 'French',
                    'code'      => 'fr',
                ),
                array(
                    'name'      => 'English',
                    'code'      => 'en',
                ),
            );

        foreach ($languages as $lang) {
            $language       = new \Duf\AdminBundle\Entity\Language();
            $language->setName($lang['name']);
            $language->setCode($lang['code']);
            $language->setEnabled(true);
            $language->setIsAdmin(true);
            $language->setCreatedAt(new \DateTime());

            $this->em->persist($language);
        }

        $this->em->flush();
    }

    private function clearCache()
    {
        $output         = $this->getCliOutput();
        $application    = $this->getCliApplication();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'cache:clear',
                                    )
                                );
        $application->run($input, $output);

        return $output->fetch();
    }

    private function createDatabase()
    {
        $output         = $this->getCliOutput();
        $application    = $this->getCliApplication();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'doctrine:database:create',
                                        '--if-not-exists'   => true,
                                    )
                                );
        $application->run($input, $output);

        return $output->fetch();
    }

    private function updateDatabaseSchema()
    {
        $output         = $this->getCliOutput();
        $application    = $this->getCliApplication();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'doctrine:schema:update',
                                        '--force'           => true,
                                    )
                                );
        $application->run($input, $output);

        return $output->fetch();
    }

    private function importTranslations()
    {
        $output         = $this->getCliOutput();
        $application    = $this->getCliApplication();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'lexik:translations:import',
                                    )
                                );
        $application->run($input, $output);

        return $output->fetch();
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