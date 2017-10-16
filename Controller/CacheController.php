<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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

    public function updateDoctrineSchemaAction()
    {
        $kernel         = $this->get('kernel');
        $application    = new Application($kernel);

        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command'   => 'doctrine:schema:update',
            '--force'   => true,
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);

        $session = $this->get('session')->getFlashBag()->add('notice', $output->fetch());

        return $this->redirect($this->generateUrl('duf_admin_homepage'));
    }
}
