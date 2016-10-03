<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\Common\Annotations\AnnotationReader;

use Duf\AdminBundle\Entity\DufCoreSeo;
use Duf\AdminBundle\Form\DufAdminGenericType;

class RoutingController extends Controller
{
    public function entitiesAction($path)
    {
        // instantiate services
        $routing_service                = $this->get('duf_admin.dufadminrouting');
        $form_service                   = $this->get('duf_admin.dufadminform');
        $seo_service                    = $this->get('duf_core.dufcoreseo');

    	// get action type
    	$action_type 			        = $routing_service->getActionType($path);
    	$view_variables 		        = array();

        // get entity name
        $entity_name                    = $routing_service->getEntityName($path);

        // get entity class
        $entity_class                   = $routing_service->getEntityClass($entity_name);

        // get page title
        $page_title                     = $routing_service->getPageTitle($entity_name);

        // get seo config
        $seo_config                     = $seo_service->getSeoConfig($entity_class);

        $view_variables['page_title']   = $page_title;
        $view_variables['entity_name']  = $entity_name;
        $view_variables['seo_config']   = $seo_config;

    	switch ($action_type) {
    		case 'index':
    			$template 						= 'DufAdminBundle:Crud:entities_index.html.twig';
                $headers_properties             = $form_service->getIndexHeaders($entity_name, $entity_class);

                $view_variables['entities'] 	= $this->getViewEntities($entity_name, $action_type);
                $view_variables['headers']      = $headers_properties['headers'];
                $view_variables['properties']   = $headers_properties['properties'];
                $view_variables['create_route'] = $routing_service->getEntityRoute($entity_name, 'create');
    			break;
            case 'create':
                $created_entity                         = new $entity_class;
                $template                               = 'DufAdminBundle:Crud:entities_new.html.twig';
                $form_options_properties                = $form_service->getFormOptions($entity_name, $entity_class);

                $create_form                            = $this->createForm(DufAdminGenericType::class, $created_entity, array(
                                                                    'action'        => '',
                                                                    'method'        => 'POST',
                                                                    'duf_options'   => $form_options_properties['form_options'],
                                                                )
                                                            );

                if (null !== $seo_config)
                    $create_form                        = $seo_service->addSeoFielsToForm($create_form, $created_entity, $entity_class);

                $view_variables['create_form']          = $create_form->createView();
                $view_variables['form_properties']      = $form_options_properties['form_properties'];
                $view_variables['content_type']         = $routing_service->getContentType($entity_name);

                // create embed forms
                if (!empty($form_options_properties['form_embed'])) {
                    foreach ($form_options_properties['form_embed'] as $form_embed) {
                        $view_variables['form_embed'][] = $form_service->getEmbedFormArray($form_embed['label'], $form_embed['target_entity'], $routing_service);
                    }
                }
                break;
            case 'edit':
                $entity_id                      = $routing_service->getEntityId($path);
                $form_entity                    = $this->getDoctrine()->getRepository($entity_name)->findOneById($entity_id);
                $template                       = 'DufAdminBundle:Crud:entities_edit.html.twig';
                $form_options_properties        = $form_service->getFormOptions($entity_name, $entity_class);
                $create_form                    = $this->createForm(DufAdminGenericType::class, $form_entity, array(
                                                            'action'        => '',
                                                            'method'        => 'POST',
                                                            'duf_options'   => $form_options_properties['form_options'],
                                                        )
                                                    );

                $config_entities                = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('entities'));

                if (isset($config_entities[$entity_name]) && isset($config_entities[$entity_name]['title_field'])) {
                    $getter         = 'get' . ucfirst($config_entities[$entity_name]['title_field']);
                    if (method_exists($form_entity, $getter)) {
                        $view_variables['page_title']   = $form_entity->{$getter}();
                    }
                }

                if (null !== $seo_config)
                    $create_form                        = $seo_service->addSeoFielsToForm($create_form, $form_entity, $entity_class);

                $view_variables['create_form']          = $create_form->createView();
                $view_variables['form_properties']      = $form_options_properties['form_properties'];
                $view_variables['entity']               = $form_entity;
                $view_variables['content_type']         = $routing_service->getContentType($entity_name);

                // create embed forms
                if (!empty($form_options_properties['form_embed'])) {
                    foreach ($form_options_properties['form_embed'] as $form_embed) {
                        $view_variables['form_embed'][] = $form_service->getEmbedFormArray($form_embed['label'], $form_embed['target_entity'], $routing_service);
                    }
                }
                break;
    	}

    	if (isset($template)) {
    		return $this->render($template, $view_variables);
    	}

        return new Response('error', 404);
    }

    public function getEmbedFormByParentEntityIdAction($form_embed_label, $form_embed_class, $form_embed_entity_name, $parent_entity_id, $parent_entity_class)
    {
        $parent_entity      = null;
        $entities           = null;

        if (null !== $parent_entity_id) {
            $child_entity_property  = $this->get('duf_admin.dufadminform')->getChildEntityRelationshipProperty($form_embed_class, $parent_entity_class);

            $entities               = $this->getDoctrine()
                                           ->getRepository($form_embed_entity_name)
                                           ->findByParentEntityId($form_embed_entity_name, $child_entity_property, $parent_entity_id);

            $parent_entity          = $this->getDoctrine()->getRepository($parent_entity_class)->findOneById($parent_entity_id);
        }

        return $this->render('DufAdminBundle:Crud:embed-form-list.html.twig', array(
                'embedded_form'             => $this->get('duf_admin.dufadminform')->getEmbedFormArray($form_embed_label, $form_embed_class, null, $parent_entity),
                'embed_entities'            => $entities,
            )
        );
    }

    public function getEmbedFormByTokenAction($form_embed_label, $form_embed_class, $form_embed_entity_name, $token)
    {
        $entities           = null;
        if (null !== $token) {
            $entities       = $this->getDoctrine()
                                   ->getRepository($form_embed_entity_name)
                                   ->findByFormToken($form_embed_entity_name, $token);
        }

        return $this->render('DufAdminBundle:Crud:embed-form-list.html.twig', array(
                'embedded_form'             => $this->get('duf_admin.dufadminform')->getEmbedFormArray($form_embed_label, $form_embed_class),
                'embed_entities'            => $entities,
            )
        );
    }

    private function getViewEntities($entity_name, $action_type)
    {
        return $this->getDoctrine()->getRepository($entity_name)->findAll();
    }
}
