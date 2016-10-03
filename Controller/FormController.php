<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use Duf\AdminBundle\Entity\DufAdminEntity;
use Duf\AdminBundle\Form\DufAdminGenericType;
use Duf\CoreBundle\Entity\DufCoreSeo;

class FormController extends Controller
{
    public function formRequestAction($path, Request $request)
    {
        // instantiate services
        $routing_service            = $this->get('duf_admin.dufadminrouting');
        $form_service               = $this->get('duf_admin.dufadminform');
        $entity_tools_service       = $this->get('duf_core.dufcoreentitytools');
        $seo_service                = $this->get('duf_core.dufcoreseo');

        // get entity name
        if (null !== $request->get('form_entity_name')) {
            $entity_name            = $request->get('form_entity_name');
        }
        else {
            $entity_name            = $routing_service->getEntityName($path);
        }

        // get entity class
        $entity_class               = $routing_service->getEntityClass($entity_name);

        // get page title
        $page_title                 = $routing_service->getPageTitle($entity_name);

        // get seo config
        $seo_config                 = $seo_service->getSeoConfig($entity_class);

        if (strpos($path, '/update/')) {
            $entity_id              = $routing_service->getEntityId($path);
            $entity                 = $this->getDoctrine()->getRepository($entity_name)->findOneById($entity_id);
        }
        else {
            $entity                 = new $entity_class;
        }

        $form_options_properties    = $form_service->getFormOptions($entity_name, $entity_class);
        $form                       = $this->createForm(DufAdminGenericType::class, $entity, array(
                                            'action'        => '',
                                            'method'        => 'POST',
                                            'duf_options'   => $form_options_properties['form_options'],
                                        )
                                    );

        if (null !== $seo_config)
            $form                   = $seo_service->addSeoFielsToForm($form, $entity, $entity_class);

        $form_data                  = $request->get('duf_admin_generic');
        $translatable_request       = $this->getTranslatableContent($request, $form_data, $form_service, $entity);
        $request                    = $translatable_request['request'];

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em             = $this->getDoctrine()->getManager();

            if ($request->isXmlHttpRequest()) {
                if (null !== $request->get('parent_entity_id') && null !== $request->get('parent_entity_class')) {
                    $parent_entity_name     = $routing_service->getEntityNameFromBundle($request->get('parent_entity_class'));
                    $parent_form_options    = $form_service->getFormOptions($entity_name, $request->get('parent_entity_class'));
                    unset($parent_form_options['create_form']);

                    if (isset($parent_form_options['form_embed'])) {
                        foreach ($parent_form_options['form_embed'] as $form_embed_value) {
                            if ($form_embed_value['target_entity'] == $entity_class || $form_embed_value['target_entity'] == substr($entity_class, 1, strlen($entity_class) - 1)) {
                                $embed_setter           = $form_service->getChildEntitySetter($entity, $form_embed_value);
                                $parent_class           = $request->get('parent_entity_class');
                                $parent_entity          = $this->getDoctrine()->getRepository($parent_class)->findOneById($request->get('parent_entity_id'));

                                if (!empty($parent_entity)) {
                                    $entity->{$embed_setter}($parent_entity);
                                }
                            }
                        }
                    }
                }
                elseif (null !== $request->get('form_token')) {
                    $entity->setFormToken($request->get('form_token'));
                }
            }

            // check ManyToMany values
            foreach ($form_data as $field_name => $value) {
                if (is_array($value)) {
                    if (isset($form_options_properties['form_options'][$field_name]['parameters']['class'])) {
                        // get relation entity class
                        $relation_entity_class  = $form_options_properties['form_options'][$field_name]['parameters']['class'];

                        // get relation entity setter
                        $relation_entity_setter = $entity_tools_service->getEntitySetter($entity, $field_name, 'add');

                        foreach ($value as $relation_entity_id) {
                            $relation_entity        = $this->getDoctrine()->getRepository($relation_entity_class)->findOneById($relation_entity_id);
                            $entity->{$relation_entity_setter}($relation_entity);
                        }
                    }
                }
            }

            // TO DO : check if type is password and create encoded password
            foreach ($form_data as $field_name => $value) {
                if (isset($form_options_properties['form_options'][$field_name]) && $form_options_properties['form_options'][$field_name]['type'] == 'password') {
                    $password           = $this->get('security.password_encoder')->encodePassword($entity, $value);
                    $password_setter    = $entity_tools_service->getEntitySetter($entity, $field_name, 'set');

                    $entity->{$password_setter}($password);
                }
            }

            $em->persist($entity);
            $em->flush();

            // persist SEO content
            $entity             = $this->persistSeoContent($entity, $entity_class, $form_data, $seo_service);

            // persist translatable content
            $entity             = $this->persistTranslatableContent($em, $entity, $translatable_request['translatable']);

            if ($request->isXmlHttpRequest()) {
                $encoder        = new JsonEncoder();
                $normalizer     = new ObjectNormalizer();

                $normalizer->setIgnoredAttributes(array('user'));
                $normalizer->setCircularReferenceHandler(function ($object) {
                    return $object->getId();
                });

                $serializer     = new Serializer(array($normalizer), array($encoder));

                $json_entity    = $serializer->serialize($entity, 'json');

                return new Response($json_entity);
            }

            // persist embed forms
            if (isset($form_data['duf_admin_form_token'])) {
                $form_token             = $form_data['duf_admin_form_token'];

                if (isset($form_options_properties['form_embed']) && !empty($form_options_properties['form_embed'])) {
                    foreach ($form_options_properties['form_embed'] as $form_embed) {
                        $orphan_entities    = $this->getDoctrine()->getRepository($form_embed['target_entity'])->findByFormToken($form_embed['target_entity'], $form_token);

                        if (!empty($orphan_entities)) {
                            $setter         = $form_service->getParentEntitySetter($entity, $form_embed);

                            if (null !== $setter) {
                                foreach ($orphan_entities as $orphan_entity) {
                                    $child_setter   = $form_service->getChildEntitySetter($orphan_entity, $form_embed);
                                    if (null !== $child_setter) {
                                        $entity->{$setter}($orphan_entity);

                                        $orphan_entity->{$child_setter}($entity);
                                        $orphan_entity->setFormToken(null);
                                        $em->persist($orphan_entity);
                                    }
                                }
                            }

                            $em->persist($entity);
                            $em->flush();
                        }
                    }
                }
            }

            // get redirect route
            $redirect_url = $routing_service->getEntityRoute($entity_name, 'index');

            return $this->redirect($redirect_url);
        }
        else {
            echo '<pre>'; print_r($request->get('duf_admin_generic')); echo '</pre>';

            foreach ($form->getErrors(true) as $form_error) {
                var_dump($form_error->getMessage());

                echo '<pre>'; print_r($form_error->getMessageParameters()); echo '</pre>';
            }

            exit('form contains errors');
        }

        if ($request->isXmlHttpRequest()) {
            $error_response = array(
                    'message'       => 'error',
                );
            return new JsonResponse($error_response);
        }

        // render form with errors
        return $this->render('DufAdminBundle:Crud:entities_new.html.twig', array(
                'content_type'          => $routing_service->getContentType($entity_name),
                'create_form'           => $form->createView(),
                'entity_name'           => $entity_name,
                'page_title'            => $page_title,
                'form_properties'       => $form_options_properties['form_properties'],
            )
        );
    }

    public function deleteRequestAction($id, $entity_name)
    {       
        $routing_service    = $this->get('duf_admin.dufadminrouting');
        $entity             = $this->getDoctrine()->getRepository($entity_name)->findOneById($id);

        if (!empty($entity)) {
            $em         = $this->getDoctrine()->getManager();
            $em->remove($entity);
            $em->flush();

            // get redirect route
            $redirect_url = $routing_service->getEntityRoute($entity_name, 'index');

            return $this->redirect($redirect_url);
        }

        return new Response('entity not found', 404);
    }

    public function deleteEmbedEntityAction($embed_entity_class, $embed_entity_id)
    {
        $embed_entity = $this->getDoctrine()->getRepository($embed_entity_class)->findOneById($embed_entity_id);
        if (!empty($embed_entity)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($embed_entity);
            $em->flush();

            return new Response('ok', 200);
        }

        return new Response('error', 500);
    }

    private function getTranslatableContent($request, $form_data, $form_service, $entity)
    {
        $translatable   = array();
        $default_lang   = $this->getParameter('locale');

        foreach ($form_data as $field_name => $field_value) {
            if (is_array($field_value)) {
                foreach ($field_value as $translate_name => $translate_value) {
                    if (strpos($translate_name, 'translate_') !== false) {
                        $lang_name  = str_replace('translate_', '', $translate_name);

                        if ($default_lang !== $lang_name) {
                            $setter     = $form_service->getParentEntitySetter($entity, array('parent_property' => $field_name), 'set');

                            $translatable[$lang_name][$field_name] = array(
                                    'setter'    => $setter,
                                    'lang'      => $lang_name,
                                    'value'     => $translate_value,
                                );
                        }

                    }
                }

                // unset value
                foreach ($field_value as $translate_name => $translate_value) {
                    if ($translate_name !== 'translate_' . $default_lang) {
                        unset($form_data[$field_name][$translate_name]);
                    }
                    else {
                        $default_lang_value = $translate_value;
                    }
                }

                if (isset($default_lang_value)) {
                    // set single value for field
                    $form_data[$field_name] = $default_lang_value;
                }

                $request->request->set('duf_admin_generic', $form_data);
            }
        }

        return array(
                'request'       => $request,
                'translatable'  => $translatable,
            );
    }

    private function persistTranslatableContent($em, $entity, $translatable)
    {
        if (!empty($translatable)) {
            $default_lang   = $this->getParameter('locale');

            foreach ($translatable as $translate_lang => $translate_fields) {
                if ($translate_lang !== $default_lang) {
                    foreach ($translate_fields as $field_name => $params) {
                        if (strpos($field_name, 'seo_') === false) {
                            $entity->setTranslatableLocale($translate_lang);

                            if (!empty($params['value']) && strlen($params['value']) > 0) {
                                $entity->{$params['setter']}($params['value']);
                            }
                            else {
                                $entity->{$params['setter']}(null);
                            }
                        }
                    }

                    $em->persist($entity);
                    $em->flush();
                }
            }
        }

        return $entity;
    }

    private function persistSeoContent($entity, $entity_class, $form_data, $seo_service)
    {
        $em         = $this->getDoctrine()->getManager();
        $seo_fields = $seo_service->getSeoFields();

        foreach ($seo_fields as $seo_field) {
            if (isset($form_data[$seo_field['name']]) && !empty($form_data[$seo_field['name']])) {
                $filters = array(
                        'entity_id'     => $entity->getId(),
                        'entity_class'  => $entity_class,
                        'seo_type'      => $seo_field['name'],
                    );

                // persist translatable SEO field
                if (is_array($form_data[$seo_field['name']])) {
                    foreach ($form_data[$seo_field['name']] as $translatable_field_name => $translatable_field_value) {
                        $locale             = str_replace('translate_', '', $translatable_field_name);
                        $filters['locale']  = $locale;

                        // check if seo sentity already exists
                        $seo_entity     = $this->getDoctrine()->getRepository('DufCoreBundle:DufCoreSeo')->findOneBy($filters);

                        if (empty($seo_entity))
                            $seo_entity     = new DufCoreSeo();

                        $seo_entity = $this->setSeoEntity($seo_entity, $entity, $entity_class, $translatable_field_value, $seo_field, $locale);
                        $em->persist($seo_entity);
                    }
                }
                else {
                    // check if seo sentity already exists
                    $seo_entity     = $this->getDoctrine()->getRepository('DufCoreBundle:DufCoreSeo')->findOneBy($filters);

                    if (empty($seo_entity))
                        $seo_entity     = new DufCoreSeo();

                    $seo_entity = $this->setSeoEntity($seo_entity, $entity, $entity_class, $form_data[$seo_field['name']], $seo_field);
                    $em->persist($seo_entity);
                }
            }
        }

        $em->flush();

        return $entity;
    }

    private function setSeoEntity($seo_entity, $entity, $entity_class, $seo_value, $seo_field, $locale = null)
    {
        // get default locale
        if (null === $locale)
            $locale = $this->getParameter('locale');

        $seo_entity->setSeoType($seo_field['name']);
        $seo_entity->setSeoValue($seo_value);
        $seo_entity->setEntityId($entity->getId());
        $seo_entity->setEntityClass($entity_class);
        $seo_entity->setLocale($locale);

        if (null !== $seo_entity->getId()) {
            $seo_entity->setUpdatedAt(new \DateTime());
        }
        else {
            $seo_entity->setCreatedAt(new \DateTime());
        }

        return $seo_entity;
    }
}
