<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\Common\Annotations\AnnotationReader;

use Duf\AdminBundle\Entity\DufCoreSeo;
use Duf\AdminBundle\Form\DufAdminGenericType;
use Duf\AdminBundle\Form\DufAdminGenericNestedTreeType;

class RoutingController extends Controller
{
    public function entitiesAction($path)
    {
        // instantiate services
        $routing_service                = $this->get('duf_admin.dufadminrouting');
        $form_service                   = $this->get('duf_admin.dufadminform');
        $seo_service                    = $this->get('duf_core.dufcoreseo');
        $ecommerce_service              = $this->get('duf_ecommerce.dufecommerce');

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

        // current aggregator service
        $view_variables['aggregator_service']   = (stripos($entity_name, 'DufAggregatorBundle:AggregatorAccount') !== false && isset($_GET['service'])) ? $_GET['service']: null;

    	switch ($action_type) {
    		case 'index':
    			$template                               = 'DufAdminBundle:Crud:entities_index.html.twig';
                $headers_properties                     = $form_service->getIndexHeaders($entity_name, $entity_class);

                $view_variables['entities']             = $this->getViewEntities($entity_name, $action_type);
                $view_variables['headers']              = $headers_properties['headers'];
                $view_variables['properties']           = $headers_properties['properties'];
                $view_variables['create_route']         = $routing_service->getEntityRoute($entity_name, 'create');
                $view_variables['is_tree']              = $routing_service->isTree($entity_name);
                $view_variables['is_exportable']        = $routing_service->isExportable($entity_name);

                // get average execution duration if is cron task
                if (strpos('DufCoreBundle:DufCoreCronTask', $entity_name) !== false) {
                    foreach ($view_variables['entities'] as $cron_task) {
                        $execution_average          = 0;
                        $execution_average_count    = 0;

                        // get DufCoreCronTaskTrace for this task
                        $cron_traces = $this->getDoctrine()->getRepository('DufCoreBundle:DufCoreCronTaskTrace')->findBy(
                            array(
                                'cronTask' => $cron_task,
                            )
                        );

                        if (!empty($cron_traces)) {
                            foreach ($cron_traces as $cron_trace) {
                                $execution_average += $cron_trace->getDuration();
                                $execution_average_count++;
                            }

                            if ($execution_average_count > 0)
                                $execution_average = $execution_average / $execution_average_count;
                        }
                    }

                    array_splice($view_variables['headers'], 4, 0, array('Average Duration'));
                    array_splice($view_variables['properties'], 4, 0, array(
                            array(
                                'name'              => 'dufcorecron_average_duration',
                                'relation_entity'   => null,
                                'relation_index'    => null,
                                'is_boolean'        => 0,
                                'value'             => number_format($execution_average, 4) . ' sec.',
                            )
                        )
                    );
                }
    			break;
            case 'create':
                $created_entity                         = new $entity_class;
                $template                               = 'DufAdminBundle:Crud:entities_new.html.twig';
                $form_options_properties                = $form_service->getFormOptions($entity_name, $entity_class);
                $generic_form_class                     = $routing_service->getEntityGenericForm($created_entity);

                $create_form                            = $this->createForm($generic_form_class, $created_entity, array(
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

                // set view variables for ecommerce
                $this->setViewVariablesForECommerce($view_variables, $entity_name, $entity_class);

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
                $form_options_properties        = $form_service->getFormOptions($entity_name, $entity_class, $form_entity);
                $generic_form_class             = $routing_service->getEntityGenericForm($form_entity);

                $create_form                    = $this->createForm($generic_form_class, $form_entity, array(
                                                            'action'        => '',
                                                            'method'        => 'POST',
                                                            'duf_options'   => $form_options_properties['form_options'],
                                                        )
                                                    );

                $config_entities                = $this->getConfigEntities();

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

                // set view variables for ecommerce
                $this->setViewVariablesForECommerce($view_variables, $entity_name, $entity_class, $entity_id);

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
        if (stripos($entity_name, 'DufAggregatorBundle:AggregatorAccount') !== false && isset($_GET['service']))
            return $this->getDoctrine()->getRepository('DufAggregatorBundle:AggregatorAccount')->findBy(
                array(
                    'service'   => $_GET['service'],
                )
            );
        
        return $this->getDoctrine()->getRepository($entity_name)->findAll();
    }

    private function getConfigEntities()
    {
        $entities               = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('entities'));
        $ecommerce_entities     = $this->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('ecommerce_entities'));

        if (is_array($ecommerce_entities))
            return array_merge($entities, $ecommerce_entities);

        return $entities;
    }

    private function setViewVariablesForECommerce(&$view_variables, $entity_name, $entity_class, $entity_id = null)
    {
        $config_service                 = $this->get('duf_admin.dufadminconfig');
        $routing_service                = $this->get('duf_admin.dufadminrouting');
        $ecommerce_service              = $this->get('duf_ecommerce.dufecommerce');

        // check if entity is product
        $view_variables['is_product']           = $routing_service->isProduct($entity_name);

        // get ecommerce entities
        $categories_entity_name              = $ecommerce_service->getCategoryEntityName();

        if (null !== $categories_entity_name)
            $view_variables['categories_class']     = $categories_entity_name;

        // if entity is product, get more details
        if ($view_variables['is_product']) {
            $view_variables['price_type']       = $ecommerce_service->getPriceType($entity_class);

            if (null !== $categories_entity_name) {
                // get categories and categories entity name
                $view_variables['categories']           = $this->getDoctrine()->getRepository($categories_entity_name)->childrenHierarchy();

                // get categories for this product
                if (null !== $entity_id) {
                    $view_variables['product_categories']   = $ecommerce_service->getProductCategories($entity_id, $entity_name, $categories_entity_name, true);
                }
            }
        }

        // check if entity is store
        $view_variables['is_store']             = $routing_service->isStore($entity_name);

        // if entity is store, get more details
        if ($view_variables['is_store']) {
            $view_variables['gmap_key']         = $this->getParameter('gmap_api_key');

            if (null !== $categories_entity_name) {
                // get list of product categories
                $view_variables['categories']   = $this->getDoctrine()->getRepository($categories_entity_name)->childrenHierarchy();
            }

            if (null !== $entity_id) {
                // get list of products for this store
                $view_variables['products']     = $ecommerce_service->getStoreProducts($entity_name, $entity_id, true);
            }
        }
    }

    private function array_insert($arr, $insert, $position) {
        $i = 0;

        foreach ($arr as $key => $value) {
            if ($i == $position) {
                foreach ($insert as $ikey => $ivalue) {
                    $ret[$ikey] = $ivalue;
                }
            }
            
            $ret[$key] = $value;
            $i++;
        }

        return $ret;
    }
}
