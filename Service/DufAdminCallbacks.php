<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\ORM\EntityManager as EntityManager;

class DufAdminCallbacks
{
    private $container;
    private $em;

    protected $parent;
    protected $action;
    protected $action_time;
    protected $entity_name;
    protected $callbacks;
    protected $form_request;
    protected $form_entity;

    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->em                   = $entityManager;
        $this->container            = $container;
    }

    public function setParent($parent)
    {
        $this->parent   = $parent;
    }

    public function initCallbacks($entity_name, $form_request, $form_entity)
    {
        $this->entity_name      = $entity_name;
        $this->callbacks        = $this->setCallbacks();
        $this->form_request     = $form_request;
        $this->form_entity      = $form_entity;

        return $this;
    }

    public function executeCallback($action, $action_time, $entity_name = null)
    {
        $this->action           = $action;
        $this->action_time      = $action_time;

        if (null !== $entity_name)
            $this->entity_name      = $entity_name;

        if (!$this->hasCallbacks())
            return;

        $callback_parameters    = $this->getCallbacks();

        if (isset($callback_parameters['controller']) && isset($callback_parameters['action'])) {
            $action_options     = array(
                    'form_entity'        => $this->form_entity,
                    'form_request'       => $this->form_request,
                );

            $controller         = new $callback_parameters['controller'];
            $action_results     = $controller->{$callback_parameters['action']}($action_options);

            if (isset($action_results['form_entity']))
                $this->form_entity  = $action_results['form_entity'];

            if (isset($action_results['form_request']))
                $this->form_request  = $action_results['form_request'];
        }
    }

    public function getEntityAfterCallback()
    {
        return $this->form_entity;
    }

    public function getFormRequestAfterCallback()
    {
        return $this->form_request;
    }

    private function setCallbacks()
    {
        $entities   = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('entities');

        if (array_key_exists($this->entity_name, $entities) && array_key_exists('callbacks', $entities[$this->entity_name])) {
            return $entities[$this->entity_name]['callbacks'];
        }

        return null;
    }

    private function hasCallbacks()
    {
        return (null !== $this->getCallbacks()) ? true : false;
    }

    private function getCallbacks()
    {
        if (null !== $this->callbacks) {
            if (array_key_exists($this->action, $this->callbacks) && array_key_exists($this->action_time, $this->callbacks[$this->action])) {
                return $this->callbacks[$this->action][$this->action_time];
            }
        }

        return null;
    }
}