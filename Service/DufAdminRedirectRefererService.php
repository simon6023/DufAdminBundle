<?php
namespace Duf\Bundle\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class DufAdminRedirectRefererService
{
    public function getRefererRoute($request, $router)
    {
        $session 	= $request->getSession();
        $referer 	= $request->headers->get('referer');
        $base_url 	= $request->getBaseUrl();

        if (null !== $base_url && strlen($base_url) > 1) {
	        $path 	= substr($referer, strpos($referer, $base_url));
	        $path 	= str_replace($base_url, '', $path);
        }

        $matcher 	= $router->getMatcher();
        $parameters = $matcher->match($path);
        $route 		= $parameters['_route'];

        $parameters_to_send 	= array();
        $excluded_params 		= array('_route');

        foreach ($parameters as $key => $param) {
        	if (!in_array($key, $excluded_params)) {
        		$parameters_to_send[$key] = $param;
        	}
        }

        $route 	= $router->generate($route, $parameters_to_send);

        // correct Facebook Connect bug
        $route 	= str_replace('#_=_', 'replace', $route);

        return $route;
    }
}