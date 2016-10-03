<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DufAdminRouting
{
    private $container;
    private $user_entity;
    private $user_role_entity;
    private $user_embed_entities;
    private $language_entity;

    public function __construct(Container $container)
    {
        $this->container            = $container;
        $this->user_entity          = $container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_entity'));
        $this->user_role_entity     = $container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_role_entity'));
        $this->user_embed_entities  = $container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_embed_entities'));
        $this->language_entity      = $container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('language_entity'));
    }

    public function getRouteFromEntityName($entity_name, $route_prefix, $action, $entity_id = null)
    {
        $entity_name = strtolower($entity_name);
        $entity_name = str_replace(':', '/', $entity_name);

        if (null !== $entity_id) {
            return $route_prefix . '/' . $entity_name . '/' . $action . '/' . $entity_id;
        }

        return $route_prefix . '/' . $entity_name . '/' . $action;
    }

    public function getEntityId($path)
    {
        $path_parts = explode('/', $path);
        return end($path_parts);
    }

    public function getActionType($path)
    {
        $path_parts = explode('/', $path);

        // edit action type
        if (strpos($path, '/edit/')) {
            return 'edit';
        }

        return end($path_parts);
    }

    public function getEntityNameFromBundle($entity_class)
    {
        $entity_name = str_replace('/Entity/', ':', $entity_class);
        $entity_name = str_replace('\Entity\\', ':', $entity_class);

        $entity_name = str_replace('/', '', $entity_name);
        $entity_name = str_replace('\\', '', $entity_name);

        return $entity_name;
    }

    public function getEntityName($path)
    {
        $entity_name    = null;
        $is_user        = false;
        $is_lang        = false;

        if (substr($path, 0, 6) === 'users/' || substr($path, 0, 19) === 'form-request/users/') {
            $is_user    = true;
        }

        if (substr($path, 0, 10) === 'languages/' || substr($path, 0, 23) === 'form-request/languages/') {
            $is_lang    = true;
        }

        if ($is_user) {
            $config_entities = array();
            $config_entities[] = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_entity'));
            $config_entities[] = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_role_entity'));

            foreach ($config_entities as $config_entity_name) {
                $config_entity_route = $this->getRouteFromEntityName($config_entity_name, '', '');
                if (strpos($path, $config_entity_route) !== false) {
                    $entity_name = $config_entity_name;
                }
            }
        }
        elseif ($is_lang) {
            $entity_name = $this->language_entity;
        }
        else {
            $config_entities = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('entities'));

            foreach ($config_entities as $config_entity_name => $params) {
                $config_entity_route = $this->getRouteFromEntityName($config_entity_name, '', '');
                if (strpos($path, $config_entity_route) !== false) {
                    $entity_name = $config_entity_name;
                }
            }
        }

        return $entity_name;
    }

    public function getEntityClass($entity_name)
    {
        $entity_class           = str_replace(':', '\Entity\\', $entity_name);

        // check if class exists
        if (!class_exists($entity_class)) {
            preg_match_all('/[A-Z]/', $entity_name, $matches, PREG_OFFSET_CAPTURE);
                
            if (isset($matches[0]))
                $matches = $matches[0];

            // get second uppercase letter
            if (isset($matches[1])) {
                $start_upper = (int)$matches[1][1];
                $entity_class = substr_replace($entity_class, '\\', $start_upper, 0);
            }
        }

        if ($entity_name == $this->user_entity || $entity_name == $this->user_role_entity || in_array($entity_name, $this->user_embed_entities)) {
            return $entity_class;
        }

        return '\\' . $entity_class;
    }

    public function getPageTitle($entity_name)
    {
        $page_title             = '';
        $config_entities        = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('entities'));

        if ($entity_name == $this->user_entity) {
            return 'Users';
        }

        if ($entity_name == $this->user_role_entity) {
            return 'User roles';
        }

        if ($entity_name == $this->language_entity) {
            return 'Languages';
        }

        foreach ($config_entities as $config_entity_name => $params) {
            if ($entity_name == $config_entity_name) {
                return $params['title'];
            }
        }

        return null;
    }

    public function getEntityRoute($entity_name, $action_type)
    {
        $router         = $this->container->get('router');
        $content_type   = $this->getContentType($entity_name);

        $route_name = 'duf_admin_entity_index';
        if ($content_type === 'users') {
            $route_name = 'duf_admin_entity_index_users';
        }
        elseif ($content_type == 'languages') {
            $route_name = 'duf_admin_entity_index_languages';
        }

        return $router->generate($route_name,
            array(
                    'path' => $this->getRouteFromEntityName($entity_name, $content_type, $action_type),
                )
            );
    }

    public function getEntityRouteName($entity_name)
    {
        $content_type   = $this->getContentType($entity_name);

        $route_name = 'duf_admin_entity_index';
        if ($content_type === 'users') {
            $route_name = 'duf_admin_entity_index_users';
        }
        elseif ($content_type === 'languages') {
            $route_name = 'duf_admin_entity_index_languages';
        }

        return $route_name;
    }

    public function getContentType($entity_name)
    {
        $content_type = 'content';
        if ($entity_name == $this->user_entity || $entity_name == $this->user_role_entity) {
            $content_type = 'users';
        }
        elseif ($entity_name == $this->language_entity) {
            $content_type = 'languages';
        }
        elseif (in_array($entity_name, $this->user_embed_entities)) {
            $content_type = 'users';
        }

        return $content_type;
    }
}