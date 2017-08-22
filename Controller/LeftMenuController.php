<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LeftMenuController extends Controller
{
    public function renderAction()
    {
        // get custom sections
        $sections           = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('sidebar_sections'));

    	// get managable entities
    	$entities 			= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('entities'));

        // get managable ecommerce_entities
        $ecommerce_entities = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('ecommerce_entities'));
        $ecommerce_products = $this->getEcommerceEntities('products', $ecommerce_entities);
        $ecommerce_stores   = $this->getEcommerceEntities('stores', $ecommerce_entities);

    	// get user entity
    	$user_entity 		= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_entity'));

    	// get user role entity
    	$user_role_entity 	= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_role_entity'));

        // get redmine link if defined
        $redmine            = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('redmine'));

        // get ecommerce config
        $ecommerce          = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('ecommerce_enabled'));

        // get aggregator config
        $aggregator          = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('aggregator_enabled'));

        // get aggregator services if enabled
        $aggregator_services = ($this->container->has('duf_aggregator.dufaggregatorconfig') && $aggregator) ? $this->get('duf_aggregator.dufaggregatorconfig')->getServices(true): null;

        // get list of bundles
        $bundles            = $this->container->getParameter('kernel.bundles');
        $sitemap            = (isset($bundles['DufCoreBundle'])) ? true : false;
        $cron               = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('cron_enabled'));
        $messaging          = (isset($bundles['DufMessagingBundle'])) ? true : false;

        return $this->render('DufAdminBundle:Default:sidebar.html.twig', array(
                'sections'              => $sections,
        		'entities' 				=> $entities,
                'ecommerce_entities'    => $ecommerce_entities,
                'ecommerce_products'    => $ecommerce_products,
                'ecommerce_stores'      => $ecommerce_stores,
        		'user_entity' 			=> $user_entity,
        		'user_role_entity' 		=> $user_role_entity,
                'redmine'               => $redmine,
                'sitemap'               => $sitemap,
                'cron'                  => $cron,
                'messaging'             => $messaging,
                'ecommerce'             => $ecommerce,
                'aggregator'            => $aggregator,
                'aggregator_services'   => $aggregator_services,
        	)
        );
    }

    private function getEcommerceEntities($type, &$ecommerce_entities)
    {
        $entities   = array();

        foreach ($ecommerce_entities as $entity_name => $params) {
            if ($type == 'products' && isset($params['is_product']) && true === $params['is_product']) {
                $entities[$entity_name] = $params;
                unset($ecommerce_entities[$entity_name]);
            }
            elseif ($type == 'stores' && isset($params['is_store']) && true === $params['is_store']) {
                $entities[$entity_name] = $params;
                unset($ecommerce_entities[$entity_name]);
            }
        }

        return $entities;
    }
}
