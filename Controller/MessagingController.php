<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Duf\MessagingBundle\Entity\Message;
use Duf\MessagingBundle\Entity\MessageUser;
use Duf\MessagingBundle\Entity\Conversation;
use Duf\MessagingBundle\Entity\ConversationUser;
use Duf\MessagingBundle\Entity\Draft;
use Duf\MessagingBundle\Entity\DraftUser;

class MessagingController extends Controller
{
    public function indexAction($page = 1)
    {
        $conversations = $this->getMessagingService()->getConversations($this->getUser());

        return $this->render('DufAdminBundle:Messaging:index.html.twig', array(
                'messaging_section_title'   => 'Inbox',
                'conversations'             => $conversations,
            )
        );
    }

    public function sentAction()
    {
        $conversations = $this->getMessagingService()->getConversations($this->getUser(), array('isAuthor' => true));

        return $this->render('DufAdminBundle:Messaging:index.html.twig', array(
                'messaging_section_title'   => 'Sent',
                'conversations'             => $conversations,
            )
        );
    }

    public function draftsAction()
    {
        $entities   = $this->getDoctrine()->getRepository('DufMessagingBundle:Draft')->findAll();

        return $this->render('DufAdminBundle:Messaging:index.html.twig', array(
                'messaging_section_title'   => 'Drafts',
                'drafts'                    => $entities,
            )
        );
    }

    public function trashAction($page = 1)
    {
        $conversations = $this->getMessagingService()->getConversations($this->getUser(), array('isDeleted' => true));

        return $this->render('DufAdminBundle:Messaging:index.html.twig', array(
                'messaging_section_title'   => 'Trash',
                'conversations'             => $conversations,
            )
        );
    }

    public function newAction()
    {
        // get messaging service
        $messaging_service  = $this->getMessagingService();

        // get possible recepients list
        $users              = $this->getMessagingUsers();

        // create form
        $form               = $messaging_service->getCreateForm(null, $users);

        return $this->render('DufAdminBundle:Messaging:new.html.twig', array(
                'form'      => $form->createView(),
            )
        );
    }

    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // get form data
        $form_data          = $request->get('message');

        // get messaging service
        $messaging_service  = $this->getMessagingService();

        // create new conversation
        $conversation       = $messaging_service->getNewConversation($form_data);

        // create Message entity
        $message            = new Message();

        // get possible recepients list
        $users              = $this->getMessagingUsers();

        // create form
        $form               = $messaging_service->getCreateForm($message, $users);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $conv_msg = $messaging_service->createMessage($message, $conversation, $form_data, $this->getUser());

            return $this->redirect($this->generateUrl('duf_admin_messaging_index'));
        }
        else {
            exit('error');
        }
    }

    public function getReplyFormAction($conversation_id, $message_id, $type)
    {
        $conversation       = $this->getDoctrine()->getRepository('DufMessagingBundle:Conversation')->findOneById($conversation_id);
        $message            = $this->getDoctrine()->getRepository('DufMessagingBundle:Message')->findOneById($message_id);
        $form               = $this->getMessagingService()->setConversation($conversation)->getCreateForm(null, $this->getMessagingUsers($conversation, $message, $type));

        return $this->render('DufAdminBundle:Messaging:reply-form.html.twig', array(
                'form'          => $form->createView(),
                'conversation'  => $conversation,
            )
        );
    }

    public function replyAction($conversation_id, Request $request)
    {
        $conversation       = $this->getDoctrine()->getRepository('DufMessagingBundle:Conversation')->findOneById($conversation_id);
        $conv_msg           = $this->getMessagingService()->createMessage(new Message(), $conversation, $request->get('message'), $this->getUser());

        $response           = array(
                'message_id'    => $conv_msg['message']->getId(),
            );

        return new JsonResponse($response);
    }

    public function readAction($conversation_id)
    {
        $conversation       = $this->getDoctrine()->getRepository('DufMessagingBundle:Conversation')->findOneById($conversation_id);
        $this->getMessagingService()->setReadConversation($this->getUser(), $conversation);

        return $this->render('DufAdminBundle:Messaging:read.html.twig', array(
                'conversation'  => $conversation,
            )
        );
    }

    public function renderMessagingLeftMenuAction($current)
    {
        return $this->render('DufAdminBundle:Messaging:left-menu.html.twig', array(
                'current'   => strtolower($current),
            )
        );
    }

    public function getMessageAction($message_id)
    {
        $message            = $this->getDoctrine()->getRepository('DufMessagingBundle:Message')->findOneById($message_id);
        $conversation       = $message->getConversation();
        $show_message       = $this->getMessagingService()->isUserInConversation($conversation, $this->getUser());

        if (!$show_message) {
            $message = null;
        }

        return $this->render('DufAdminBundle:Messaging:message.html.twig', array(
                'message'           => $message,
                'conversation'      => $conversation,
            )
        );
    }

    public function deleteConversationAction($conversation_id)
    {
        $conversation       = $this->getDoctrine()->getRepository('DufMessagingBundle:Conversation')->findOneById($conversation_id);
        $proceed            = $this->getMessagingService()->isUserInConversation($conversation, $this->getUser());

        if ($proceed) {
            $delete         = $this->getMessagingService()->deleteConversation($conversation, $this->getUser());

            if ($delete) {
                return new Response('ok', 200);
            }
        }

        return new Response('error', 500);
    }

    public function saveDraftAction(Request $request)
    {
        $draft      = $this->getMessagingService()->createDraft($request, $this->getUser());

        return new Response('success', 200);
    }

    public function readDraftAction($draft_id)
    {
        $draft   = $this->getDoctrine()->getRepository('DufMessagingBundle:Draft')->findOneById($draft_id);

        // get messaging service
        $messaging_service  = $this->getMessagingService();

        // get possible recepients list
        $users              = $this->getMessagingUsers();

        // create form
        $form               = $messaging_service->getCreateForm(null, $users);

        // get draft selected users
        $draft_users        = array();
        foreach ($draft->getUsers() as $draft_user) {
            $draft_users[$draft_user->getUser()->getUsername()] = $draft_user->getUser()->getId();
        }

        $form->get('content')->setData($draft->getContent());
        $form->get('subject')->setData($draft->getSubject());
        $form->get('users')->setData(array_keys($draft_users));

        return $this->render('DufAdminBundle:Messaging:edit-draft.html.twig', array(
                'draft'             => $draft,
                'form'              => $form->createView(),
                'selected_users'    => json_encode($draft_users),
            )
        );
    }

    private function getMessagingService()
    {
        return $this->get('duf_messaging.messaging');
    }

    private function getMessagingUsers($conversation = null, $message = null, $type = null)
    {
        if (null !== $conversation) {
            $_users = array();
            foreach ($conversation->getUsers() as $conversation_user) {
                if ($conversation_user->getUser() !== $this->getUser()) {
                    if (null !== $type) {
                        // if user replies to own message, send it to all previous recipients
                        if ($message->getAuthor() == $this->getUser()) {
                            foreach ($message->getUsers() as $msg_users) {
                                $_users[] = $msg_users->getUser();
                            }
                        }
                        else {
                            if ($type == 'reply') {
                                $_users[] = $message->getAuthor();
                            }
                            elseif ($type == 'reply-all') {
                                $_users[] = $conversation_user->getUser();
                            }
                        }
                    }
                    else {
                        $_users[] = $conversation_user->getUser();
                    }
                }
            }
        }
        else {
            $_users = $this->getDoctrine()->getRepository('DufAdminBundle:User')->findBy(
                    array(
                        'isActive'          => true,
                        'optinMessages'     => true,
                    )
                );
        }

        $users = array();

        foreach ($_users as $user) {
            if ($user !== $this->getUser()) {
                $users[$user->getUsername()] = $user->getId();
            }
        }

        return $users;
    }
}
