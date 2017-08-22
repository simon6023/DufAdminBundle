<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Intl\Intl;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager as EntityManager;

use Duf\AdminBundle\Form\DufAdminGenericType;

class DufAdminForm
{
    private $em;
    private $container;
    private $token_storage;
    private $mapping_classes;

    public function __construct(EntityManager $entityManager, Container $container, TokenStorage $token_storage)
    {
        $this->em                   = $entityManager;
        $this->container            = $container;
        $this->token_storage        = $token_storage;
        $this->mapping_classes      = array(
                                        'Doctrine\ORM\Mapping\ManyToOne',
                                        'Doctrine\ORM\Mapping\OneToMany',
                                        'Doctrine\ORM\Mapping\OneToOne',
                                        'Doctrine\ORM\Mapping\ManyToMany',
                                    );
        $this->excluded_properties = array(
                                        'id',
                                        'created_at',
                                        'updated_at',
                                        'form_token'
                                    );
    }

    public function getFormOptions($entity_name, $entity_class, $entity = null)
    {
        $form_options           = array();
        $form_properties        = array();
        $form_embed_tmp         = array();
        $form_embed             = array();
        $_entity_class          = $entity_class;

        $entity_properties      = $this->getEntityProperties($entity_class);
        $annotationReader       = new AnnotationReader();

        foreach ($entity_properties as $property_name) {
            $entity_class               = $_entity_class;

            $process_property           = false;
            if (property_exists($entity_class, $property_name)) {
                $process_property       = true;
            }
            else {
                // get parent entity
                $reflection_class = new \ReflectionClass($entity_class);
                if(!$reflection_class->isAbstract()){
                    $entity_class   = get_parent_class(new $entity_class);
                    if (property_exists($entity_class, $property_name))
                        $process_property   = true;
                }
            }

            if ($process_property) {
                $reflectionClass        = new \ReflectionProperty($entity_class, $property_name);
                $annotations            = $annotationReader->getPropertyAnnotations($reflectionClass);
                $relationship_entity    = null;
                $is_multiple            = false;

                // check if property is relationship
                foreach ($annotations as $annotation) {
                    if (in_array(get_class($annotation), $this->mapping_classes)) {
                        $relationship_entity = $annotation->targetEntity;

                        if (get_class($annotation) == 'Doctrine\ORM\Mapping\ManyToMany') {
                            $is_multiple = true;
                        }
                    }
                }

                foreach ($annotations as $annotation) {
                    if (get_class($annotation) == 'Duf\AdminBundle\Annotations\EditableAnnotation') {
                        if ($annotation->is_editable) {
                            if ($annotation->type !== 'embed') {
                                $form_options[$property_name]      = array(
                                        'property_name'     => $property_name,
                                        'type'              => $annotation->type,
                                        'parameters'        => array(
                                                'label'             => $annotation->label,
                                                'required'          => $annotation->required,
                                                'attr'              => array(
                                                        'placeholder'      => $annotation->placeholder
                                                    ),
                                            ),
                                    );

                                $excluded_annotation_types = array('hidden', 'entity_hidden');

                                if (null !== $relationship_entity && !in_array($annotation->type, $excluded_annotation_types)) {
                                    $form_options[$property_name]['parameters']['class']            = $relationship_entity;
                                    $form_options[$property_name]['parameters']['choice_label']     = $annotation->relation_index;
                                    $form_options[$property_name]['parameters']['multiple']         = $is_multiple;
                                }

                                if (isset($annotation->order) && !empty($annotation->order)) {
                                    $order = $annotation->order;
                                }
                                else {
                                    $order = rand(999, 1500);
                                }

                                if ($annotation->type !== 'hidden') {
                                    $form_properties[$order] = $property_name;
                                }

                                if ($annotation->type == 'file' || $annotation->type == 'multiple_file') {
                                    $form_options[$property_name]['parameters']['parent_entity']    = $relationship_entity;
                                    $form_options[$property_name]['parameters']['parent_property']  = $property_name;
                                    $form_options[$property_name]['parameters']['filetype']         = $annotation->filetype;
                                }

                                if ($annotation->type == 'datetime') {
                                    $form_options[$property_name]['parameters']['format']           = 'dd/MM/yyyy HH:mm';
                                    $form_options[$property_name]['parameters']['widget']           = 'single_text';
                                }

                                if ($annotation->type == 'date') {
                                    $form_options[$property_name]['parameters']['format']           = 'dd/MM/yyyy';
                                    $form_options[$property_name]['parameters']['widget']           = 'single_text';
                                }

                                if ($annotation->type == 'number') {
                                    $form_options[$property_name]['parameters']['number_type']      = $annotation->number_type;
                                }

                                if ($annotation->type == 'entity') {
                                    // check if entity is interface
                                    if (isset($form_options[$property_name]['parameters']['class'])) {
                                        exit('check if entity is interface');
                                    }

                                    $form_options[$property_name]['parameters']['multiple']         = $annotation->multiple;

                                    if (isset($annotation->empty_value) && true === $annotation->empty_value) {
                                        $form_options[$property_name]['parameters']['entity_empty'] = true;
                                    }
                                }

                                if ($annotation->type == 'choice') {
                                    if (isset($annotation->choices) && !empty($annotation->choices)) {
                                        $form_options[$property_name]['parameters']['choices']      = $annotation->choices;
                                    }
                                }

                                if ($annotation->type == 'entity_hidden') {
                                    $config_user_entity     = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('user_entity');
                                    $form_options[$property_name]['class']  = ('user_entity' !== $annotation->class) ? $annotation->class: $config_user_entity;

                                    if ($annotation->hidden_value === 'current_user') {
                                        $form_options[$property_name]['parameters']['data']         = $this->token_storage->getToken()->getUser()->getId();
                                    }
                                }   

                                $cron_annotation_types = array('day_picker', 'hour_picker', 'minute_picker');
                                if (in_array($annotation->type, $cron_annotation_types)) {
                                    $form_options[$property_name]['parameters']['mapped']   = false;
                                    $form_options[$property_name]['parameters']['multiple'] = true;

                                    if ($annotation->type === 'day_picker') {
                                        $form_options[$property_name]['parameters']['choices'] = $this->getDays();

                                        if (null !== $entity)
                                            $form_options[$property_name]['parameters']['days'] = $this->getCronTaskSelectedValues('days', $entity);
                                    }

                                    if ($annotation->type === 'hour_picker') {
                                        $form_options[$property_name]['parameters']['choices'] = $this->getHours();

                                        if (null !== $entity)
                                            $form_options[$property_name]['parameters']['hours'] = $this->getCronTaskSelectedValues('hours', $entity);
                                    }

                                    if ($annotation->type === 'minute_picker') {
                                        $form_options[$property_name]['parameters']['choices'] = $this->getMinutes();

                                        if (null !== $entity)
                                            $form_options[$property_name]['parameters']['minutes'] = $this->getCronTaskSelectedValues('minutes', $entity);
                                    }
                                }
                            }
                            else {
                                $form_embed_tmp[] = array(
                                        'name'                  => $property_name,
                                        'reflection_class'      => $reflectionClass,
                                        'label'                 => $annotation->label,
                                    );
                            }
                        }
                    }
                }
            }
        }

        // set prices field if product entity with multiple prices
        if ($this->container->get('duf_admin.dufadminrouting')->isProduct($entity_name)) {
            // set prices form field
            $form_options['prices']      = array(
                'property_name'     => 'prices',
                'type'              => 'prices',
                'parameters'        => array(
                        'label'             => 'Prices',
                        'required'          => false,
                        'currencies'        => $this->container->get('duf_ecommerce.dufecommerce')->getCurrencies(true),
                    ),
            );

            // set categories form field
            $form_options['categories']  = array(
                'property_name'     => 'categories',
                'type'              => 'text',
                'parameters'        => array(
                    'mapped'        => false,
                ),
            );
        }

        // set form fields if store entity
        if ($this->container->get('duf_admin.dufadminrouting')->isStore($entity_name)) {
            // set country choices
            $countries                                          = Intl::getRegionBundle()->getCountryNames();
            $form_options['country']['parameters']['choices']   = array_flip($countries);

            // set products field
            $form_options['products']  = array(
                'property_name'     => 'products',
                'type'              => 'text',
                'parameters'        => array(
                    'mapped'        => false,
                ),
            );
        }        

        if (!empty($form_embed_tmp)) {
            foreach ($form_embed_tmp as $embed_tmp) {
                $embed_annotations  = $annotationReader->getPropertyAnnotations($embed_tmp['reflection_class']);

                foreach ($embed_annotations as $embed_annotation) {
                    if (isset($embed_annotation->targetEntity)) {
                        $form_embed[]   = array(
                                'label'             => $embed_tmp['label'],
                                'target_entity'     => $embed_annotation->targetEntity,
                                'parent_class'      => $embed_tmp['reflection_class']->class,
                                'parent_property'   => $embed_tmp['name'],
                                'child_property'    => $embed_annotation->mappedBy,
                            );
                    }
                }
            }
        }

        ksort($form_properties);

        return array(
                'form_options'          => $form_options,
                'form_properties'       => $form_properties,
                'form_embed'            => $form_embed,
            );
    }

    public function getEntityProperties($entity)
    {
        return array_keys($this->em->getMetadataFactory()->getMetadataFor($entity)->reflFields);
    }

    public function getEmbeddedFormName($form_name)
    {
        $form_name = preg_replace('~[^\pL\d]+~u', '-', $form_name);
        $form_name = iconv('utf-8', 'us-ascii//TRANSLIT', $form_name);
        $form_name = preg_replace('~[^-\w]+~', '', $form_name);
        $form_name = trim($form_name, '-');
        $form_name = preg_replace('~-+~', '-', $form_name);
        $form_name = strtolower($form_name);

        if (empty($form_name)) {
            return 'n-a';
        }

        return $form_name;
    }

    public function getChildEntityRelationshipProperty($entity_class, $parent_entity_class)
    {
        $child_entity_properties        = $this->getEntityProperties($entity_class);
        $annotationReader               = new AnnotationReader();

        foreach ($child_entity_properties as $child_property) {
            if (!in_array($child_property, $this->excluded_properties)) {
                if (property_exists($entity_class, $child_property)) {
                    $reflectionClass        = new \ReflectionProperty($entity_class, $child_property);
                    $child_annotations      = $annotationReader->getPropertyAnnotations($reflectionClass);

                    foreach ($child_annotations as $child_annotation) {
                        if (in_array(get_class($child_annotation), $this->mapping_classes)) {
                            if ($parent_entity_class === $child_annotation->targetEntity) {
                                return $child_property;
                            }
                        }
                    }
                }
                $child_property_annotations = '';
            }
        }
    }

    public function getEmbedFormArray($form_embed_label, $form_embed_class, $routing_service = null, $parent_entity = null)
    {
        if (null === $routing_service) {
            $routing_service            = $this->container->get('duf_admin.dufadminrouting');
        }

        $embed_entity_name              = $routing_service->getEntityNameFromBundle($form_embed_class);
        $form_embed_options_properties  = $this->getFormOptions($embed_entity_name, $form_embed_class);

        $form_embed_form                = $this->container->get('form.factory')->create(DufAdminGenericType::class, new $form_embed_class, array(
                                            'action'        => '',
                                            'method'        => 'POST',
                                            'duf_options'   => $form_embed_options_properties['form_options'],
                                        )
                                    );

        if (null !== $parent_entity) {
            $parent_entity_class                = get_class($parent_entity);
            $parent_entity_name                 = $routing_service->getEntityNameFromBundle($parent_entity_class);
            $parent_entity_form_options         = $this->getFormOptions($parent_entity_name, $parent_entity_class);

            if (isset($parent_entity_form_options['form_embed'])) {
                $setter = null;
                foreach ($parent_entity_form_options['form_embed'] as $form_embed_options) {
                    if (isset($form_embed_options['target_entity']) && $form_embed_options['target_entity'] == $form_embed_class) {
                        $setter = $this->getChildEntitySetter(new $form_embed_class, $form_embed_options);
                    }
                }

                if (null !== $setter) {
                    $form_embed_form->getData()->{$setter}($parent_entity);
                }
            }
        }

        $form_embed_headers_properties  = $this->getIndexHeaders($embed_entity_name, $form_embed_class);

        return array(
            'create_form'           => $form_embed_form->createView(),
            'form_properties'       => $form_embed_options_properties['form_properties'],
            'form_name'             => $form_embed_label,
            'form_list_headers'     => $form_embed_headers_properties['headers'],
            'form_entity_name'      => $embed_entity_name,
            'form_entity_class'     => $form_embed_class,
        );
    }

    public function getIndexHeaders($entity_name, $entity_class)
    {
        $entity_properties      = $this->getEntityProperties($entity_class);
        $annotationReader       = new AnnotationReader();

        $indexable_columns          = array();
        $index_properties           = array();
        $default_indexable_columns  = array();
        $default_index_properties   = array();

        $last_order                 = rand(999, 1500);
        $order_default_properties   = rand(-999, -1500);
        $default_properties         = array('created_at', 'updated_at');

        foreach ($entity_properties as $property_name) {
            if (property_exists($entity_class, $property_name)) {
                $reflectionClass        = new \ReflectionProperty($entity_class, $property_name);
                $annotations            = $annotationReader->getPropertyAnnotations($reflectionClass);
                
                $relationship_entity    = null;

                // check if property is relationship
                foreach ($annotations as $annotation) {
                    $mapping_classes = array(
                            'Doctrine\ORM\Mapping\ManyToOne',
                            'Doctrine\ORM\Mapping\OneToMany',
                            'Doctrine\ORM\Mapping\OneToOne',
                        );

                    if (in_array(get_class($annotation), $mapping_classes)) {
                        $relationship_entity = $annotation->targetEntity;
                    }
                }

                foreach ($annotations as $annotation) {
                    if (get_class($annotation) == 'Duf\AdminBundle\Annotations\IndexableAnnotation') {
                        if ($annotation->index_column === true) {
                            if (isset($annotation->index_column_order) && !empty($annotation->index_column_order)) {
                                $order = $annotation->index_column_order;
                            }
                            else {
                                $order = $last_order++;
                            }

                            if (in_array($property_name, $default_properties))
                                $order = $order_default_properties = $order_default_properties + 1;

                            $is_boolean     = (isset($annotation->boolean_column) && true === $annotation->boolean_column) ? true : false;

                            if (in_array($property_name, $default_properties)) {
                                $default_indexable_columns[$order]  = $annotation->index_column_name;

                                $default_index_properties[$order]   = array(
                                    'name'                  => $property_name,
                                    'relation_entity'       => $relationship_entity,
                                    'relation_index'        => $annotation->relation_index,
                                    'is_boolean'            => $is_boolean,
                                );
                            }
                            else {
                                $indexable_columns[$order]  = $annotation->index_column_name;

                                $index_properties[$order]   = array(
                                    'name'                  => $property_name,
                                    'relation_entity'       => $relationship_entity,
                                    'relation_index'        => $annotation->relation_index,
                                    'is_boolean'            => $is_boolean,
                                );
                            }
                        }
                    }
                }
            }
        }

        ksort($indexable_columns);
        ksort($index_properties);

        $index_properties   = array_merge($index_properties, $default_index_properties);
        $indexable_columns  = array_merge($indexable_columns, $default_indexable_columns);

        return array(
            'headers'       => $indexable_columns,
            'properties'    => $index_properties,
        );
    }

    public function getEntityClass($entity)
    {
        return get_class($entity);
    }

    public function getParentEntitySetter($entity, $form_embed, $setter_method = 'add')
    {
        // get parent entity methods
        $parent_entity_methods = get_class_methods($entity);
        foreach ($parent_entity_methods as $method) {
            $method_start = substr($method, 0, 3);
            if ($method_start == $setter_method) {
                $search_property = strtolower($form_embed['parent_property']);
                $search_property = substr($search_property, 0, strlen($search_property) - 1);

                if (strpos(strtolower($method), $search_property) !== false) {
                    return $method;
                }

                // check for plural ending by 'y'
                $search_property = substr($search_property, 0, strlen($search_property) - 2) . 'y';
                if (strpos(strtolower($method), $search_property) !== false) {
                    return $method;
                }
            }
        }

        return null;
    }

    public function getChildEntitySetter($child_entity, $form_embed)
    {
        $child_property         = strtolower($form_embed['child_property']);
        $child_entity_methods   = get_class_methods($child_entity);

        foreach ($child_entity_methods as $method) {
            $method_start = substr($method, 0, 3);
            if ($method_start == 'set' && strpos(strtolower($method), $child_property)) {
                return $method;
            }
        }

        return null;
    }

    public function getFormFieldTypes()
    {
        return array(
                'checkbox'  => 'Checkbox',
                'choice'    => 'Choice',
                'date'      => 'Date',
                'datetime'  => 'DateTime',
                'email'     => 'Email',
                'embed'     => 'Embed',
                'entity'    => 'Entity',
                'file'      => 'File',
                'hidden'    => 'Hidden',
                'number'    => 'Number',
                'text'      => 'Text',
                'textarea'  => 'Textarea',
                'password'  => 'Password',
            );
    }

    public function getPropertyPrefix($entity, $property_name)
    {
        $entity_class       = get_class($entity);
        $entity_properties  = $this->getEntityProperties($entity_class);

        foreach ($entity_properties as $entity_property_name) {
            if ($entity_property_name === $property_name) {
                // check if property dispose of prefix
                $annotationReader       = new AnnotationReader();
                $reflectionClass        = new \ReflectionProperty($entity_class, $entity_property_name);
                $annotations            = $annotationReader->getPropertyAnnotations($reflectionClass);

                foreach ($annotations as $annotation) {
                    $indexable_class     = 'Duf\AdminBundle\Annotations\IndexableAnnotation';
                    if (get_class($annotation) == $indexable_class && isset($annotation->prefix)) {
                        return $annotation->prefix;
                    }
                }
            }
        }

        return null;
    }

    public function getPropertySuffix($entity, $property_name)
    {
        $entity_class       = get_class($entity);
        $entity_properties  = $this->getEntityProperties($entity_class);

        foreach ($entity_properties as $entity_property_name) {
            if ($entity_property_name === $property_name) {
                // check if property dispose of suffix
                $annotationReader       = new AnnotationReader();
                $reflectionClass        = new \ReflectionProperty($entity_class, $entity_property_name);
                $annotations            = $annotationReader->getPropertyAnnotations($reflectionClass);

                foreach ($annotations as $annotation) {
                    $indexable_class     = 'Duf\AdminBundle\Annotations\IndexableAnnotation';
                    if (get_class($annotation) == $indexable_class && isset($annotation->suffix)) {
                        return $annotation->suffix;
                    }
                }
            }
        }

        return null;
    }

    public function getPreviousFiles($previous_files_ids)
    {
        $previous_files         = array();

        if (null !== $previous_files_ids && is_array($previous_files_ids)) {
            foreach ($previous_files_ids as $file_id) {
                $file       = $this->em->getRepository('Duf\AdminBundle\Entity\File')->findOneById($file_id);

                if (!empty($file)) {
                    $previous_files[] = $file;
                }
            }
        }

        return $previous_files;
    }

    public function isTranslatableField($field_name, $entity_class)
    {
        $entity_properties      = $this->getEntityProperties($entity_class);

        if (in_array($field_name, $entity_properties)) {
            $annotationReader       = new AnnotationReader();

            if (property_exists($entity_class, $field_name)) {
                $reflectionClass        = new \ReflectionProperty($entity_class, $field_name);
                $annotations            = $annotationReader->getPropertyAnnotations($reflectionClass);

                foreach ($annotations as $annotation) {
                    if ('Gedmo\Mapping\Annotation\Translatable' === get_class($annotation))
                        return true;
                }
            }
        }

        return false;
    }

    public function getDays()
    {
        $days       = array();
        $timestamp  = strtotime('next Monday');

        for ($i = 1; $i <= 7; $i++) {
            $days[strftime('%A', $timestamp)] = $i;
            $timestamp  = strtotime('+1 day', $timestamp);
        }

        return $days;
    }

    public function getHours()
    {
        $hours       = array();

        for ($i = 0; $i < 24; $i++) {
            $hour_double    = (strlen($i) === 1) ? '0' . $i: $i;
            $date           = new \DateTime('2000-01-01 ' . $i . ':00:00');

            $hours[$date->format('H:i')] = $i;
        }

        return $hours;
    }

    public function getMinutes()
    {
        $minutes       = array();

        for ($i = 0; $i < 60; $i++) {
            $minutes_double = (strlen($i) === 1) ? '0' . $i: $i;

            $minutes[$minutes_double] = $i;
        }

        return $minutes;
    }

    public function getCronTaskSelectedValues($picker_type, $entity)
    {
        $cron_task_fields = $this->getCronTaskFields(false, true);

        if (!isset($cron_task_fields[$picker_type]))
            return null;

        if (!is_object($entity))
            return null;

        if (!method_exists($entity, $cron_task_fields[$picker_type]))
            return null;

        $selected_values = $entity->{$cron_task_fields[$picker_type]}();

        if (null === $selected_values || strlen($selected_values) <= 1)
            return null;

        return json_decode($selected_values);
    }

    public function getCronTaskRequest($request)
    {
        $form_data                  = $request->get('duf_admin_generic');
        $fields                     = $this->getCronTaskFields();

        foreach ($fields as $field) {
            if (!isset($form_data[$field]))
                continue;

            $selected_values = array();

            foreach ($form_data[$field] as $key => $selected_array) {
                foreach ($selected_array as $selected_key => $selected_value) {
                    $selected_values[] = $selected_value;
                }
            }

            $form_data[$field] = $selected_values;
        }

        $request->request->set('duf_admin_generic', $form_data);

        return $request;
    }

    public function getCronTaskFields($with_setter = false, $with_getter = false)
    {
        if (!$with_setter && !$with_getter)
            return array('days', 'hours', 'minutes');

        if ($with_setter && !$with_getter)
            return array(
                'days'      => 'setDays',
                'hours'     => 'setHours',
                'minutes'   => 'setMinutes',
            );

        if ($with_getter && !$with_setter)
            return array(
                'days'      => 'getDays',
                'hours'     => 'getHours',
                'minutes'   => 'getMinutes',
            );
    }
}