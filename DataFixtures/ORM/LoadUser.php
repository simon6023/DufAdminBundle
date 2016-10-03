<?php
namespace Duf\AdminBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Duf\AdminBundle\Entity\User;

class LoadUser extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	/**
	* @var ContainerInterface
	*/
	private $container;

	/**
	* {@inheritDoc}
	*/
	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
	}

	/**
	* {@inheritDoc}
	*/
	public function load(ObjectManager $manager)
	{
		$role 		= $this->container->get('doctrine')->getRepository('DufAdminBundle:UserRole')->findOneByName('ROLE_ADMIN');
		$user_infos = array(
				'username' 			=> 'admin',
				'firstname' 		=> 'admin',
				'lastname' 			=> 'admin',
				'email' 			=> 'admin@domain.com',
				'password' 			=> 'admin',
				'salt' 				=> uniqid(),
			);

		$user = new User();

       	$encoder 	= $this->container->get('security.encoder_factory')->getEncoder($user);
       	$password 	= $encoder->encodePassword($user_infos['password'], $user_infos['salt']);

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

		$manager->persist($user);
		$manager->flush();
	}

	/**
	* {@inheritDoc}
	*/
	public function getOrder()
	{
		return 2;
	}
}