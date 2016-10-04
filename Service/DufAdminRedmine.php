<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Redmine;
use Redmine\Api\IssueStatus;

use Duf\AdminBundle\Form\Type\DufAdminTextType;
use Duf\AdminBundle\Form\Type\DufAdminTextareaType;
use Duf\AdminBundle\Form\Type\DufAdminDateType;
use Duf\AdminBundle\Form\Type\DufAdminDatetimeType;
use Duf\AdminBundle\Form\Type\DufAdminCheckboxType;
use Duf\AdminBundle\Form\Type\DufAdminChoiceType;
use Duf\AdminBundle\Form\Type\DufAdminEntityType;
use Duf\AdminBundle\Form\Type\DufAdminNumberType;
use Duf\AdminBundle\Form\Type\DufAdminFileType;

class DufAdminRedmine
{
    private $container;
    private $config;
    private $user;
    private $client;
    private $statuses;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function init($user)
    {
        $this->setConfig();
        $this->setUser($user);
        $this->setClient();
        $this->setStatuses();

        return $this;
    }

    public function getIssues()
    {
        $issues     = $this->client->issue->all([
                        'limit'         => 1000,
                        'sort'          => 'id:desc',
                        'project_id'    => $this->config['project'],
            ]);

        if (isset($issues['issues'])) {
            return $issues['issues'];
        }

        return null;
    }

    public function getIssue($id)
    {
        $issue = $this->client->issue->show($id,
            array(
                    'include' => array('attachments', 'journals'),
                )
            );

        if (isset($issue['issue'])) {
            return $issue['issue'];
        }

        return null;
    }

    public function getStatusName($status_id)
    {
        $this->setStatuses();

        foreach ($this->statuses as $status) {
            if ($status['id'] == $status_id) {
                return $status['name'];
            }
        }

        return null;
    }

    public function getUserUsername($user_id)
    {
        $users  = $this->client->user->all();
        foreach ($users['users'] as $user) {
            if ($user['id'] == $user_id) {
                return $user['firstname'] . ' ' .$user['lastname'];
            }
        }

        return null;
    }

    public function getIssueForm($form_builder)
    {
        $form   = $form_builder
                       ->add('tracker', DufAdminChoiceType::class, array(
                                'label'         => 'Tracker',
                                'choices'       => $this->getApiArrayForForm('tracker', 'trackers'),
                                'required'      => true,
                            )
                       )
                       ->add('subject', DufAdminTextType::class, array(
                                'label'   => 'Subject',
                                'attr'    => array(
                                    'placeholder'      => 'Subject',
                                ),
                                'required'      => true,
                            )
                       )
                       ->add('parent_task', DufAdminChoiceType::class, array(
                                'label'         => 'Parent Task',
                                'required'      => true,
                                'choices'       => $this->getApiArrayForForm('issue', 'issues', 'subject'),
                            )
                       )
                       ->add('description', DufAdminTextAreaType::class, array(
                                'label'      => 'Description',
                            )
                       )
                       ->add('status', DufAdminChoiceType::class, array(
                                'label'         => 'Status',
                                'choices'       => $this->getApiArrayForForm('issue_status', 'issue_statuses'),
                                'required'      => true,
                            )
                       )
                       ->add('priority', DufAdminChoiceType::class, array(
                                'label'         => 'Priority',
                                'choices'       => $this->getApiArrayForForm('issue_priority', 'issue_priorities'),
                                'required'      => true,
                            )
                       )
                       ->add('assignee', DufAdminChoiceType::class, array(
                                'label'         => 'Assignee',
                                'choices'       => $this->getProjectUsers(),
                                'required'      => true,
                            )
                       )
                       ->add('private', DufAdminCheckboxType::class, array(
                                'label'         => 'Private',
                                'required'      => false,
                                'value'         => null,
                            )
                       )
                       ->add('files', FileType::class, array(
                                'label'         => 'Files',
                                'required'      => false,
                                'multiple'      => true,
                            )
                       )
                       ->getForm();

        return $form;
    }

    public function createIssue($form_data, $files)
    {
        $redmine_uploads = array();

        if (null !== $files) {
            $files = $files->get('form');
            if (isset($files['files'])) {
                $files = $files['files'];

                foreach ($files as $file) {
                    if (null !== $file && !empty($file)) {
                        $upload             = json_decode($this->client->api('attachment')->upload($file->getClientOriginalName()));

                        $redmine_uploads[]  = array(
                                'token'         => $upload->upload->token,
                                'filename'      => $file->getClientOriginalName(),
                                'content_type'  => $file->getMimeType(),
                            );                   
                    }
                }
            }
        }

        $this->client->issue->create([
            'project_id'        => $this->config['project'],
            'subject'           => $form_data['subject'],
            'description'       => $form_data['description'],
            'assigned_to_id'    => $form_data['assignee'],
            'priority_id'       => $form_data['priority'],
            'tracker_id'        => $form_data['tracker'],
            'status_id'         => $form_data['status'],
            'is_private'        => (isset($form_data['private'])) ? 1 : 0,
            'uploads'           => $redmine_uploads,
        ]);

        return true;
    }

    private function setConfig()
    {
        $this->config       = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('redmine'));
    }

    private function setUser($user)
    {
        if (null !== $user && null !== $user->getRedmineUsername()) {
            $this->user = $user;
        }

        return $this;
    }

    private function setClient()
    {
        $this->client       = new Redmine\Client($this->config['host'], $this->config['api_key']);
        $this->client->setImpersonateUser($this->user->getRedmineUsername());
    }

    private function setStatuses()
    {
        $statuses     = $this->client->api('issue_status')->all();

        if (isset($statuses['issue_statuses'])) {
            $this->statuses     = $statuses['issue_statuses'];
        }
    }

    private function getApiArrayForForm($endpoint, $result_key, $name_key = 'name')
    {
        $values   = array();
        $_values  = $this->client->api($endpoint)->all(array(
                'project_id'    => $this->config['project'],
                'limit'         => 200,
            )
        );

        if (isset($_values[$result_key])) {
            foreach ($_values[$result_key] as $value) {
                $values[$value[$name_key]] = $value['id'];
            }
        }

        return $values;
    }

    private function getProjectUsers()
    {
        $users      = array();
        $_users     = $this->client->api('membership')->all($this->config['project']);

        if (isset($_users['memberships'])) {
            foreach ($_users['memberships'] as $user) {
                $users[$user['user']['name']] = $user['user']['id'];
            }
        }

        return $users;
    }
}