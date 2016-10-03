<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CacheController extends Controller
{
    public function clearAction($type)
    {
        switch ($type) {
            case 'doctrine':
                $cacheDriver    = new \Doctrine\Common\Cache\ArrayCache();
                $deleted        = $cacheDriver->deleteAll();
                $session        = $this->get('session')->getFlashBag()->add('notice', 'Doctrine Cache cleared');
                break;
        }

        return $this->redirect($this->generateUrl('duf_admin_homepage'));
    }
}
