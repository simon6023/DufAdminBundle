<?php
namespace Duf\AdminBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Duf\AdminBundle\Entity\UserRole;

class LoadUserRole extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
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
		$roles = array('ROLE_ADMIN', 'ROLE_USER');

		foreach ($roles as $role_name) {
			$role = new UserRole();
			$role->setName($role_name);

			$manager->persist($role);
		}

		$manager->flush();
	}

	/**
	* {@inheritDoc}
	*/
	public function getOrder()
	{
		return 1;
	}
}