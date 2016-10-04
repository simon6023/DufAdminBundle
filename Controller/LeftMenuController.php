<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LeftMenuController extends Controller
{
    public function renderAction()
    {
    	// get managable entities
    	$entities 			= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('entities'));

    	// get user entity
    	$user_entity 		= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_entity'));

    	// get user role entity
    	$user_role_entity 	= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('user_role_entity'));

        // get redmine link if defined
        $redmine            = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('redmine'));

        // get list of bundles
        $bundles            = $this->container->getParameter('kernel.bundles');
        $sitemap            = (isset($bundles['DufCoreBundle'])) ? true : false;
        $messaging          = (isset($bundles['DufMessagingBundle'])) ? true : false;

        return $this->render('DufAdminBundle:Default:sidebar.html.twig', array(
        		'entities' 				=> $entities,
        		'user_entity' 			=> $user_entity,
        		'user_role_entity' 		=> $user_role_entity,
                'redmine'               => $redmine,
                'sitemap'               => $sitemap,
                'messaging'             => $messaging,
        	)
        );
    }
}
