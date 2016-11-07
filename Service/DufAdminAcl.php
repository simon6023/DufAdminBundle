<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DufAdminAcl
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function isAllowed($entity_name, $action, $user)
    {
        $action_check   = false;
        $acl            = $this->getAcl();

        // check if entity has acl
        if (isset($acl[$entity_name])) {
            // if user is undefined, deny access
            if (null !== $user && is_object($user)) {
                // check if user is granted ACL roles
                foreach ($acl[$entity_name] as $action_name => $roles) {
                    if ($action_name === $action) {
                        $action_check   = true;

                        foreach ($roles as $role_name) {
                            if ($this->container->get('security.authorization_checker')->isGranted($role_name)) {
                                return true;
                            }
                        }
                    }
                }
            }

            if (!$action_check)
                return true;

            return false;
        }

        return true;
    }

    private function getAcl()
    {
        $acl                    = array();
        $config_service         = $this->container->get('duf_admin.dufadminconfig');
        $entities               = $config_service->getDufAdminConfig('entities');

        foreach ($entities as $entity_name => $entity_config) {
            if (!isset($entity_config['acl']))
                continue;

            $acl_array          = array();

            foreach ($entity_config['acl'] as $action_name => $roles) {
                $acl_array[$action_name]    = $roles;
            }

            $acl[$entity_name]  = $acl_array;
        }

        return $acl;
    }
}