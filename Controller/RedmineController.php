<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RedmineController extends Controller
{
    public function indexAction()
    {
        $issues     = $this->getRedmine()->getIssues();

        return $this->render('DufAdminBundle:Redmine:index.html.twig', array(
                'issues'    => $issues,
            )
        );
    }

    public function viewAction($id)
    {
        $issue          = $this->getRedmine()->getIssue($id);

        //echo '<pre>'; print_r($issue); echo '</pre>'; exit();

        if (null === $issue) {
            $this->addFlash('error', 'Issue was not found.');

            return $this->redirect($this->generateUrl('duf_admin_redmine_index'));
        }

        return $this->render('DufAdminBundle:Redmine:view.html.twig', array(
                'issue'     => $issue,
            )
        );
    }

    public function createIssueAction()
    {
        return $this->render('DufAdminBundle:Redmine:create-issue.html.twig', array(
                'form'  => $this->getRedmine()->getIssueForm($this->createFormBuilder())->createView(),
            )
        );
    }

    public function saveIssueAction(Request $request)
    {
        if (null !== $request->get('form')) {
            $created = $this->getRedmine()->createIssue($request->get('form'), $request->files);

            if ($created) {
                $this->addFlash('success', 'New Issue was created.');
            }
        }
        else {
            $this->addFlash('error', 'An error occured, the issue was not created.');
        }

        return $this->redirect($this->generateUrl('duf_admin_redmine_index'));
    }

    private function getRedmine()
    {
        return $this->get('duf_admin.dufadminredmine')->init($this->getUser());
    }
}
