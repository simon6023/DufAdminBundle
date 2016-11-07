<?php
namespace Duf\AdminBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Doctrine\ORM\Event\LifecycleEventArgs;

class PermissionsControllerListener
{
	protected $token_storage;
	protected $container;

	public function __construct(TokenStorage $token_storage, Container $container)
	{
		$this->token_storage 		= $token_storage;
		$this->container 			= $container;
	}

	public function listenControllerPermissions(FilterResponseEvent $event)
	{
		if (!$event->isMasterRequest())
			return;

		$route_params 			= $event->getRequest()->attributes->get('_route_params');

		if (null !== $route_params && isset($route_params['path'])) {
			$path 				= $route_params['path'];
			$token 				= $this->token_storage->getToken();
			$user 				= (null !== $token) ? $token->getUser() : null;
			$entity_name 		= $this->container->get('duf_admin.dufadminrouting')->getEntityName($path);
			$action 			= $this->container->get('duf_admin.dufadminrouting')->getActionType($path);

			if (!$this->container->get('duf_admin.dufadminacl')->isAllowed($entity_name, $action, $user))
				throw new AccessDeniedException("You do not have permission to access this page.");
		}
	}
}