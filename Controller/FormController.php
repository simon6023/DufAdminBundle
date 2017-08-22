<?php

namespace Duf\AdminBundle\Controller;

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
use Duf\ECommerceBundle\Entity\DufECommerceProductCategory;
use Duf\ECommerceBundle\Entity\DufECommerceStoreProduct;

class FormController extends Controller
{
    public function formRequestAction($path, Request $request)
    {
        // instantiate services
        $routing_service            = $this->get('duf_admin.dufadminrouting');
        $form_service               = $this->get('duf_admin.dufadminform');
        $entity_tools_service       = $this->get('duf_core.dufcoreentitytools');
        $seo_service                = $this->get('duf_core.dufcoreseo');
        $ecommerce_service          = $this->get('duf_ecommerce.dufecommerce');

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
            $is_update              = true;
            $entity_id              = $routing_service->getEntityId($path);
            $entity                 = $this->getDoctrine()->getRepository($entity_name)->findOneById($entity_id);
        }
        else {
            $is_update              = false;
            $entity                 = new $entity_class;
        }

        $form_options_properties    = $form_service->getFormOptions($entity_name, $entity_class);
        $generic_form_class         = $routing_service->getEntityGenericForm($entity);

        $form                       = $this->createForm($generic_form_class, $entity, array(
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

        // instantiate callbacks service
        $callbacks_service          = $this
                                        ->get('duf_admin.dufadmincallbacks')
                                        ->initCallbacks($entity_name, $request, $entity);

        // CALLBACK : save | before
        if (!$is_update)
            $callbacks_service->executeCallback('save', 'before');

        // CALLBACK : update | before
        if ($is_update)
            $callbacks_service->executeCallback('update', 'before');

        $entity                     = $callbacks_service->getEntityAfterCallback();
        $request                    = $callbacks_service->getFormRequestAfterCallback();

        // format post data if Cron Task
        if ($entity_name === 'DufCoreBundle:DufCoreCronTask') {
            $request = $form_service->getCronTaskRequest($request);
            $form_data = $request->get('duf_admin_generic');
        }

        // handle form request
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
            if (null !== $form_data) {
                foreach ($form_data as $field_name => $value) {
                    if (is_array($value)) {
                        if (isset($form_options_properties['form_options'][$field_name]['parameters']['class'])) {
                            // get relation entity class
                            $relation_entity_class  = $form_options_properties['form_options'][$field_name]['parameters']['class'];

                            // get relation entity setter
                            $relation_entity_setter = $entity_tools_service->getEntitySetter($entity, $field_name, 'add');

                            foreach ($value as $relation_entity_id) {
                                $relation_entity        = $this->getDoctrine()->getRepository($relation_entity_class)->findOneById($relation_entity_id);

                                if (!empty($relation_entity)) {
                                    // don't know why but commenting this line makes things work................
                                    //$entity->{$relation_entity_setter}($relation_entity);
                                }
                            }
                        }
                    }
                }

                // check if type is password and create encoded password
                foreach ($form_data as $field_name => $value) {
                    if (isset($form_options_properties['form_options'][$field_name]) && $form_options_properties['form_options'][$field_name]['type'] == 'password') {
                        $password           = $this->get('security.password_encoder')->encodePassword($entity, $value);
                        $password_setter    = $entity_tools_service->getEntitySetter($entity, $field_name, 'set');

                        $entity->{$password_setter}($password);
                    }
                }
            }

            // persist multiple_files
            foreach ($form_data as $field_name => $value) {
                if (isset($value['multiple_files'])) {
                    // get setter
                    $multiple_files_setter      = $entity_tools_service->getEntitySetter($entity, $field_name, 'set');
                    if (null !== $multiple_files_setter) {
                        $entity->{$multiple_files_setter}($value['multiple_files']);
                    }
                }
            }

            // json encode values for Cron Task
            if ($entity_name === 'DufCoreBundle:DufCoreCronTask') {
                $cron_fields = $form_service->getCronTaskFields(true);

                foreach ($cron_fields as $cron_field_name => $cron_field_setter) {
                    if (!isset($form_data[$cron_field_name]))
                        continue;

                    if (!method_exists($entity, $cron_field_setter))
                        continue;

                    $entity->{$cron_field_setter}(json_encode($form_data[$cron_field_name]));
                }
            }

            // CALLBACK : persist | before
            $callbacks_service->executeCallback('persist', 'before');

            $entity                     = $callbacks_service->getEntityAfterCallback();
            $request                    = $callbacks_service->getFormRequestAfterCallback();

            // set service type if aggregator account
            if (stripos($entity_name, 'DufAggregatorBundle:AggregatorAccount') !== false && isset($_GET['service']))
                $entity->setService($_GET['service']);

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

            // persist categories if is product
            if ($routing_service->isProduct($entity_name) && isset($form_data['categories'])) {
                $product_categories     = $form_data['categories'];

                foreach ($product_categories as $categories_entity_name => $category_ids) {
                    // delete categories that are not used anymore
                    // get product categories
                    $previous_product_categories     = $ecommerce_service->getProductCategories($entity->getId(), $entity_name, $categories_entity_name, true);

                    if (!empty($previous_product_categories)) {
                        foreach ($previous_product_categories as $previous_product_category) {
                            if (!in_array($previous_product_category['id'], $category_ids)) {
                                // get DufECommerceProductCategory entity
                                $product_category_to_remove = $this->getDoctrine()->getRepository('DufECommerceBundle:DufECommerceProductCategory')->findOneBy(
                                    array(
                                        'product_id'        => $entity->getId(),
                                        'category_id'       => $previous_product_category['id'],
                                        'category_entity'   => $categories_entity_name,
                                        'product_entity'    => $entity_name,
                                    )
                                );

                                if (!empty($product_category_to_remove)) {
                                    $em->remove($product_category_to_remove);
                                }
                            }
                        }

                        $em->flush();
                    }

                    foreach ($category_ids as $category_id) {
                        // check if DufECommerceProductCategory entity exists
                        $check_product_category = $this->getDoctrine()->getRepository('DufECommerceBundle:DufECommerceProductCategory')->findOneBy(
                            array(
                                'product_id'        => $entity->getId(),
                                'category_id'       => $category_id,
                                'category_entity'   => $categories_entity_name,
                                'product_entity'    => $entity_name,
                            )
                        );

                        if (empty($check_product_category)) {
                            // create DufECommerceProductCategory entity
                            $category_entity    = new DufECommerceProductCategory();
                            $category_entity->setProductId($entity->getId());
                            $category_entity->setCategoryId($category_id);
                            $category_entity->setCategoryEntity($categories_entity_name);
                            $category_entity->setProductEntity($entity_name);

                            $em->persist($category_entity);
                        }
                    }
                }

                $em->flush();
            }

            // persist products if is store
            if ($routing_service->isStore($entity_name) && isset($form_data['products']) && !empty($form_data['products'])) {
                // get previous store products
                $previous_store_products     = $ecommerce_service->getStoreProducts($entity_name, $entity->getId(), true);

                foreach ($previous_store_products as $previous_store_product) {
                    $delete_store_product = true;

                    foreach ($form_data['products'] as $selected_product_check) {
                        $selected_product_elements   = explode('|', $selected_product_check);
                        $selected_product_id         = $selected_product_elements[0];
                        $selected_product_class      = $selected_product_elements[1];

                        if ($selected_product_class == $previous_store_product['class_name'] && $selected_product_id == $previous_store_product['id']) {
                            $delete_store_product = false;
                        }
                    }

                    if ($delete_store_product) {
                        $previous_store_product_entity    = $this->getDoctrine()->getRepository('DufECommerceBundle:DufECommerceStoreProduct')->findOneBy(
                            array(
                                'product_entity'    => $previous_store_product['class_name'],
                                'product_id'        => $previous_store_product['id'],
                                'store_entity'      => $entity_name,
                                'store_id'          => $entity->getId(),
                            )
                        );

                        $em->remove($previous_store_product_entity);
                    }
                }

                $em->flush();

                // persist products
                foreach ($form_data['products'] as $selected_product) {
                    $product_elements   = explode('|', $selected_product);
                    $product_id         = $product_elements[0];
                    $product_class      = $product_elements[1];

                    // check if product is already associated to store
                    $check_store_product    = $this->getDoctrine()->getRepository('DufECommerceBundle:DufECommerceStoreProduct')->findOneBy(
                        array(
                            'product_entity'    => $product_class,
                            'product_id'        => $product_id,
                            'store_entity'      => $entity_name,
                            'store_id'          => $entity->getId(),
                        )
                    );

                    if (empty($check_store_product)) {
                        $store_product      = new DufECommerceStoreProduct();
                        $store_product->setProductId($product_id);
                        $store_product->setStoreId($entity->getId());
                        $store_product->setProductEntity($product_class);
                        $store_product->setStoreEntity($entity_name);

                        $em->persist($store_product);
                    }
                }

                $em->flush();
            }

            // CALLBACK : save | after
            if (!$is_update)
                $callbacks_service->executeCallback('save', 'after');

            // CALLBACK : update | after
            if ($is_update)
                $callbacks_service->executeCallback('update', 'after');

            // get redirect route
            $redirect_url = $routing_service->getEntityRoute($entity_name, 'index');

            if (
                stripos($entity_name, 'DufAggregatorBundle:AggregatorAccount') !== false 
                && stripos($redirect_url, 'service=') === false 
                && isset($_GET['service'])
            )
                $redirect_url .= '?service=' . $_GET['service'];

            return $this->redirect($redirect_url);
        }
        else {
            echo '<pre>'; print_r($request->get('duf_admin_generic')); echo '</pre>';

            foreach ($form->getErrors(true) as $key => $form_error) {
                var_dump($key);
                echo '<br>';
                var_dump($form_error->getMessage());
                echo '<br>';
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
        $entity_class       = $routing_service->getEntityClass($entity_name);

        if (substr($entity_class, 0, 1) == '\\') {
            $entity_class = substr($entity_class, 1, strlen($entity_class));
        }

        if (!empty($entity)) {
            $entity_id  = $entity->getId();
            $em         = $this->getDoctrine()->getManager();
            $em->remove($entity);
            $em->flush();

            // delete translations
            $translations   = $this->getDoctrine()->getRepository('Gedmo\Translatable\Entity\Translation')->findBy(
                    array(
                        'objectClass'      => $entity_class,
                        'foreignKey'       => $entity_id,
                    )
                );

            if (!empty($translations)) {
                foreach ($translations as $translation) {
                    $em->remove($translation);
                }

                $em->flush();
            }

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

        if (empty($form_data)) {
            return array(
                    'request'       => $request,
                    'translatable'  => $translatable,
                );
        }

        foreach ($form_data as $field_name => $field_value) {
            // check if field has @Gedmo\Translatable annotation
            $is_translatable_field  = $form_service->isTranslatableField($field_name, get_class($entity));

            if (is_array($field_value) && $is_translatable_field) {
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
