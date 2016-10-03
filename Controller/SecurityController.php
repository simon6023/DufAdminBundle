<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class SecurityController extends Controller
{
    public function loginAction(Request $request)
    {
		$authenticationUtils 	= $this->get('security.authentication_utils');
	    $error 					= $authenticationUtils->getLastAuthenticationError();
	    $lastUsername 			= $authenticationUtils->getLastUsername();

	    return $this->render('DufAdminBundle:Security:login.html.twig',
	        array(
	            'last_username' => $lastUsername,
	            'error'         => $error,
	        )
	    );
    }

    public function logoutAction()
    {
    	$this->get('security.token_storage')->setToken(null);
    	$this->get('request')->getSession()->invalidate();

    	return $this->redirect($this->generateUrl('duf_admin_homepage'));
    }
}
