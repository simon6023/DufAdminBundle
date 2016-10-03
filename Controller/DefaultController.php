<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
    	//$this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        return $this->render('DufAdminBundle:Default:index.html.twig');
    }

    public function homepageAction()
    {
    	return $this->render('DufAdminBundle:Default:homepage.html.twig');
    }

    public function renderHeaderAction()
    {
        $routing_service    = $this->get('duf_admin.dufadminrouting');
    	$site_name 	        = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('site_name'));
        $tasks              = $this->getDoctrine()->getRepository('DufAdminBundle:Task')->findByHeaderTasks($this->getUser(), 5);
        $create_task_route  = $routing_service->getEntityRoute('DufAdminBundle:Task', 'create');
        $notifications      = $this->getUnreadNotifications($this->getUser());

    	return $this->render('DufAdminBundle:Default:header.html.twig', array(
    			'site_name' 		=> $site_name,
                'tasks'             => $tasks,
                'create_task_route' => $create_task_route,
                'notifications'     => $notifications,
    		)
    	);
    }

    public function renderControlSidebarAction()
    {
        $langs = $this->getDoctrine()->getRepository('DufAdminBundle:Language')->findBy(
            array(
                'enabled' => true,
                'isAdmin' => true,
            )
        );

        return $this->render('DufAdminBundle:Default:control-sidebar.html.twig', array(
                'langs'     => $langs,
            )
        );
    }

    public function phpinfoAction()
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
        $phpinfo = preg_replace('%<table(.*)>%', '<table$1 class="table">', $phpinfo);

        return $this->render('DufAdminBundle:Default:phpinfo.html.twig', array('phpinfo' => $phpinfo));
    }

    private function getUnreadNotifications($user)
    {
        $notifications      = array();
        $target_classes     = $this->getDoctrine()->getRepository('DufCoreBundle:DufCoreNotificationType')->findByDistinctTargetClasses();

        foreach ($target_classes as $target_class) {
            if (class_exists($target_class)) {
                $class              = new $target_class($this->getDoctrine()->getManager(), $this->container);
                $notifications[]    = $class->getNotifications($this->getUser());
            }
        }

        return $notifications;
    }
}
