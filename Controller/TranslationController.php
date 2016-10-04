<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class TranslationController extends Controller
{
    public function changeLocaleAction($locale = 'en', Request $request)
    {
        $lang               = $this->getDoctrine()->getRepository('DufAdminBundle:Language')->findOneByCode($locale);
        $current_language   = $request->getSession()->get('_locale');

        if (!empty($lang)) {
            $request->setLocale($lang->getCode());
            $request->getSession()->set('_locale', $lang->getCode());
        }

        $redirectService    = $this->get('duf_admin.dufadminredirectrefererservice');
        $route              = $redirectService->getRefererRoute($request, $this->get('router'));

        return $this->redirect($route);
    }

    public function exportAction()
    {
        $kernel         = $this->get('kernel');
        $application    = new Application($kernel);
        $output         = new BufferedOutput();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'lexik:translations:export',
                                    )
                                );


        $application->setAutoExit(false);               
        $application->run($input, $output);

        // get console output
        $output->fetch();

        return $this->redirect($this->generateUrl('duf_admin_homepage'));
    }
}
