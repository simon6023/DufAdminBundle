<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Duf\AdminBundle\Form\DufAdminGenericType;

class ModalController extends Controller
{
    public function renderModalAction($name, Request $request)
    {
        // instantiate services
        $routing_service        = $this->get('duf_admin.dufadminrouting');
        $form_service           = $this->get('duf_admin.dufadminform');

        $view_variables         = array();
        $embed_form_array       = array();

        if (null !== $request->get('parent_entity_class')) {
            $parent_entity_class                    = $request->get('parent_entity_class');
            $parent_entity_name                     = $routing_service->getEntityNameFromBundle($parent_entity_class);

            // get metadata form
            $form_options_properties                = $form_service->getFormOptions($parent_entity_name, $parent_entity_class);

            // create embed forms
            if (!empty($form_options_properties['form_embed'])) {
                foreach ($form_options_properties['form_embed'] as $form_embed) {
                    if (null !== $request->get('parent_property') && $request->get('parent_property') == $form_embed['parent_property']) {
                        if (null !== $request->get('parent_entity_id')) {
                            $parent_entity                      = $this->getDoctrine()->getRepository($parent_entity_class)->findOneById($request->get('parent_entity_id'));
                        }
                        else {
                            $parent_entity                      = null;
                        }

                        $embed_form_array                       = $form_service->getEmbedFormArray($form_embed['label'], $form_embed['target_entity'], $routing_service);

                        $view_variables['parent_entity']        = $parent_entity;
                        $view_variables['form_embed']           = $embed_form_array;
                        $view_variables['content_type']         = $routing_service->getContentType($parent_entity_name);

                        if (null !== $request->get('parent_entity_id')) {
                            $parent_entity                      = $this->getDoctrine()->getRepository($parent_entity_class)->findOneById($request->get('parent_entity_id'));
                            $view_variables['embed_entities']   = $this->getDoctrine()
                                                                    ->getRepository($form_embed['target_entity'])
                                                                    ->findByParentEntityId($form_embed['target_entity'], $form_embed['child_property'], $request->get('parent_entity_id'));
                        }
                    }
                }
            }

            if ($name == 'select-file') {
                $parent_entity_class                = $request->get('parent_entity_class');
                $parent_entity_id                   = $request->get('parent_entity_id');
                $view_variables['parent_entity']    = $this->getDoctrine()->getRepository($parent_entity_class)->findOneById($parent_entity_id);
                $view_variables['parent_property']  = $request->get('parent_property');
                $view_variables['filetype']         = $request->get('filetype');
            }

            $create_form                            = $this->createForm(DufAdminGenericType::class, new $parent_entity_class, array(
                                                                'action'        => '',
                                                                'method'        => 'POST',
                                                                'duf_options'   => $form_options_properties['form_options'],
                                                            )
                                                        );

            $view_variables['create_form']          = $create_form->createView();

            if (isset($embed_form_array['form_properties'])) {
                $view_variables['form_properties']      = $embed_form_array['form_properties'];
            }
        }

        $view_variables['modal_title']              = $request->get('modal_title');
        
        return $this->render('DufAdminBundle:Modal:' . $name . '.html.twig', $view_variables);
    }
}
