<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EntityManagerController extends Controller
{
    private $entity_file;
    private $otm_entity_file;
    private $linebreak;
    private $tab;
    private $bundle;
    private $namespace;
    private $entity_name;
    private $one_to_many_classes_to_create = array();

    public function __construct()
    {
        $this->linebreak    = "\n";
        $this->tab          = "\t";
    }

    public function indexAction()
    {
        $entities_and_bundles   = $this->getEntitiesAndBundles();

        return $this->render('DufAdminBundle:EntityManager:index.html.twig', array(
                'entities'      => $entities_and_bundles['entities'],
                'bundles'       => $entities_and_bundles['bundles'],
            )
        );
    }

    public function newAction(Request $request)
    {
        if (null !== $request->get('bundle') && null !== $request->get('entity_name')) {
            $bundle                 = $request->get('bundle');
            $entity_name            = ucfirst($request->get('entity_name'));
            $entities_and_bundles   = $this->getEntitiesAndBundles();

            return $this->render('DufAdminBundle:EntityManager:create.html.twig', array(
                    'field_types'   => $this->getFieldTypes(),
                    'field_nbr'     => 1,
                    'entities'      => $entities_and_bundles['entities'],
                    'bundles'       => $entities_and_bundles['bundles'],
                    'bundle'        => $bundle,
                    'entity_name'   => $entity_name,
                )
            );
        }

        $this->addFlash('error', 'No Bundle or entity name selected.');

        return $this->redirect($this->generateUrl('duf_admin_create_entity_index'));
    }

    public function createAction(Request $request)
    {
        if (null !== $request->get('bundle') && null !== $request->get('entity_name')) {
            $this->bundle           = $request->get('bundle');
            $this->entity_name      = ucfirst($request->get('entity_name'));
            $table_name             = strtolower($this->entity_name);

            $this->namespace        = $this->bundle . '\Entity';
            $file_dir               = $this->getEntityDirectory($this->bundle);

            $fields                 = $request->get('entity_field');

            if (null !== $file_dir && null !== $fields) {
                //echo '<pre>'; print_r($fields); echo '</pre>'; exit();

                $file_name              = $this->entity_name . '.php';
                $this->entity_file      = fopen($file_dir . $file_name, 'w');

                // php tags
                fwrite($this->entity_file, '<?php ');
                fwrite($this->entity_file, $this->linebreak);

                // namespace
                fwrite($this->entity_file, $this->linebreak);
                fwrite($this->entity_file, 'namespace ' . $this->namespace . ';');

                // write use
                $this->writeUse('doctrine');
                $this->writeUse('duf_admin_entity');
                $this->writeUse('duf_admin_annotations');

                // entity declaration
                $this->writeEntityDeclaration($this->entity_name, $table_name);

                // class declaration
                $this->writeClassDeclaration($this->entity_name);

                foreach ($fields as $field) {
                    $this->writeField($field);
                }

                // close class declaration
                fwrite($this->entity_file, $this->linebreak);
                fwrite($this->entity_file, '}');

                fclose($this->entity_file);

                // create OneToMany entities
                if (!empty($this->one_to_many_classes_to_create)) {
                    foreach ($this->one_to_many_classes_to_create as $otm_entity) {
                        $this->writeOneToManyEntityClass($otm_entity);
                    }
                }

                // generate getters and setters
                $doctrine_generate_entities_response    = $this->doctrineGenerateEntities($this->bundle, $this->entity_name);
                $doctrine_schema_update_response        = $this->doctrineSchemaUpdate();

                $this->addFlash('success', 'Entity was created.');
                $this->addFlash('doctrine_generate_entities', $doctrine_generate_entities_response);
                $this->addFlash('doctrine_schema_update', $doctrine_schema_update_response);

                return $this->redirect($this->generateUrl('duf_admin_create_entity_index'));
            }
        }

        $this->addFlash('error', 'No Bundle or entity name selected.');

        return $this->redirect($this->generateUrl('duf_admin_create_entity_index'));
    }

    public function getFieldsetAction($field_nbr)
    {
        $entities_and_bundles   = $this->getEntitiesAndBundles();

        return $this->render('DufAdminBundle:EntityManager:fieldset.html.twig', array(
                'field_nbr'     => $field_nbr,
                'field_types'   => $this->getFieldTypes(),
                'entities'      => $entities_and_bundles['entities'],
                'bundles'       => $entities_and_bundles['bundles'],
            )
        );
    }

    public function getFieldOptionsAction($field_type, $field_nbr)
    {
        return $this->render('DufAdminBundle:EntityManager:fieldset-options.html.twig', array(
                'field_type'    => $this->getFieldTypes($field_type),
                'field_nbr'     => $field_nbr,
            )
        );
    }

    public function getDufAdminAnnotationOptionsAction($annotation_type, $field_nbr, $selected_entity)
    {
        $form_field_types   = $this->get('duf_admin.dufadminform')->getFormFieldTypes();
        $entity_properties  = (null !== $selected_entity) ? $this->get('duf_admin.dufadminform')->getEntityProperties($selected_entity) : null;
        $filetypes          = $this->get('duf_admin.dufadminfile')->getFileTypes();

        return $this->render('DufAdminBundle:EntityManager:' . $annotation_type . '-options.html.twig', array(
                'field_nbr'             => $field_nbr,
                'form_field_types'      => $form_field_types,
                'entity_properties'     => $entity_properties,
                'filetypes'             =>$filetypes,
            )
        );
    }

    public function getMappedByFieldAction($field_nbr)
    {
        return $this->render('DufAdminBundle:EntityManager:one-to-many-options.html.twig', array(
                'field_nbr'             => $field_nbr,
            )
        );
    }

    public function getInversedByFieldAction($field_nbr)
    {
        return $this->render('DufAdminBundle:EntityManager:many-to-many-options.html.twig', array(
                'field_nbr'             => $field_nbr,
            )
        );
    }

    private function doctrineGenerateEntities($bundle, $entity_name)
    {
        $kernel         = $this->get('kernel');
        $application    = new Application($kernel);
        $output         = new BufferedOutput();
        $input          = new ArrayInput(
                                array(
                                    'command'           => 'doctrine:generate:entities',
                                    'name'              => $bundle . ':' . $entity_name,
                                    )
                                );


        $application->setAutoExit(false);               
        $application->run($input, $output);

        // get console output
        return $output->fetch();
    }

    private function doctrineSchemaUpdate()
    {
        $kernel         = $this->get('kernel');
        $application    = new Application($kernel);
        $output         = new BufferedOutput();
        $input          = new ArrayInput(
                                array(
                                        'command'           => 'doctrine:schema:update',
                                        '--force'           => true,
                                    )
                                );


        $application->setAutoExit(false);
        $application->run($input, $output);

        // get console output
        return $output->fetch();
    }

    private function writeUse($type, $is_otm_entity = false)
    {
        $file_to_write = $this->entity_file;
        if ($is_otm_entity) {
            $file_to_write = $this->otm_entity_file;
        }

        switch ($type) {
            case 'doctrine':
                // use Doctrine ORM
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, 'use Doctrine\ORM\Mapping as ORM;');
                break;
            case 'duf_admin_entity':
                // use DufAdminEntity
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, 'use Duf\AdminBundle\Entity\DufAdminEntity;');
                break;
            case 'duf_admin_annotations':
                // use DufAdmin's annotations
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, 'use Duf\AdminBundle\Annotations\IndexableAnnotation as Indexable;');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, 'use Duf\AdminBundle\Annotations\EditableAnnotation as Editable;');
                break;
        }
    }

    private function writeEntityDeclaration($entity_name, $table_name, $is_otm_entity = false)
    {
        $file_to_write = $this->entity_file;
        if ($is_otm_entity) {
            $file_to_write = $this->otm_entity_file;
        }

        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, '/**');
        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, ' * ' . $entity_name);
        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, ' *');
        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, ' * @ORM\Table(name="' . $table_name . '")');
        fwrite($file_to_write, $this->linebreak);

        if ($is_otm_entity) {
            fwrite($file_to_write, '* @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\DufAdminRepository")');
        }
        else {
            fwrite($file_to_write, ' * @ORM\Entity()');
        }

        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, ' */');
    }

    private function writeClassDeclaration($entity_name, $is_otm_entity = false)
    {
        $file_to_write = $this->entity_file;
        if ($is_otm_entity) {
            $file_to_write = $this->otm_entity_file;
        }

        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, 'class ' . $entity_name . ' extends DufAdminEntity');
        fwrite($file_to_write, $this->linebreak);
        fwrite($file_to_write, '{');
        fwrite($file_to_write, $this->linebreak);
    }

    private function writeField($field, $is_otm_entity = false)
    {
        $nullable   = (isset($field['nullable']) && $field['nullable'] == '1') ? ', nullable=true' : '';

        $file_to_write = $this->entity_file;
        if ($is_otm_entity) {
            $file_to_write = $this->otm_entity_file;
        }

        switch ($field['field_type']) {
            case 'text':
                $field_length = (isset($field['length'])) ? $field['length'] : 255;

                fwrite($file_to_write, $this->tab . '/**');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @var string');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' *');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @ORM\Column(name="' . $field['field_name'] . '", type="string", length=' . $field_length . $nullable.')');
                fwrite($file_to_write, $this->linebreak);

                $this->writeFieldDufAdminAnnotations($field);

                fwrite($file_to_write, $this->tab . ' */');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . 'private $' . lcfirst($field['field_name']) . ';');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                break;
            case 'date':
            case 'datetime':
                fwrite($file_to_write, $this->tab . '/**');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @var \DateTime');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' *');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @ORM\Column(name="' . $field['field_name'] . '", type="datetime"'. $nullable .')');
                fwrite($file_to_write, $this->linebreak);

                $this->writeFieldDufAdminAnnotations($field);

                fwrite($file_to_write, $this->tab . ' */');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . 'private $' . lcfirst($field['field_name']) . ';');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                break;
            case 'boolean':
                fwrite($file_to_write, $this->tab . '/**');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @var bool');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' *');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @ORM\Column(name="' . $field['field_name'] . '", type="boolean"' . $nullable . ')');
                fwrite($file_to_write, $this->linebreak);

                $this->writeFieldDufAdminAnnotations($field);

                fwrite($file_to_write, $this->tab . ' */');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . 'private $' . lcfirst($field['field_name']) . ';');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                break;
            case 'integer':
                fwrite($file_to_write, $this->tab . '/**');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @var integer');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' *');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @ORM\Column(name="' . $field['field_name'] . '", type="integer"' . $nullable . ')');
                fwrite($file_to_write, $this->linebreak);

                $this->writeFieldDufAdminAnnotations($field);

                fwrite($file_to_write, $this->tab . ' */');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . 'private $' . lcfirst($field['field_name']) . ';');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                break;
            case 'float':
                fwrite($file_to_write, $this->tab . '/**');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @var float');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' *');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . ' * @ORM\Column(name="' . $field['field_name'] . '", type="float"' . $nullable . ')');
                fwrite($file_to_write, $this->linebreak);

                $this->writeFieldDufAdminAnnotations($field);

                fwrite($file_to_write, $this->tab . ' */');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->tab . 'private $' . lcfirst($field['field_name']) . ';');
                fwrite($file_to_write, $this->linebreak);
                fwrite($file_to_write, $this->linebreak);
                break;
            case 'relationship':
                switch ($field['relationship_type']) {
                    case 'ManyToOne':
                        if ($nullable !== '') {
                            $nullable = str_replace(', ', '', $nullable);
                        }

                        fwrite($file_to_write, $this->tab . '/**');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . ' * @ORM\ManyToOne(targetEntity="' . $field['relationship_entity'] . '")');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . ' * @ORM\JoinColumn(' . $nullable . ')');
                        fwrite($file_to_write, $this->linebreak);

                        $field['field_type'] = 'entity';
                        $this->writeFieldDufAdminAnnotations($field);
                        $field['field_type'] = 'relationship';

                        fwrite($file_to_write, $this->tab . ' */');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . 'protected $' . lcfirst($field['field_name']) . ';');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->linebreak);
                        break;
                    case 'ManyToMany':
                        $inversed_by = (isset($field['is_inversed_by'])) ? ', inversedBy="' . $field['is_inversed_by'] . '"' : '';

                        fwrite($file_to_write, $this->tab . '/**');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . ' * @ORM\ManyToMany(targetEntity="' . $field['relationship_entity'] . '"' . $inversed_by . ')');
                        fwrite($file_to_write, $this->linebreak);

                        $field['field_type'] = 'entity';
                        $this->writeFieldDufAdminAnnotations($field);
                        $field['field_type'] = 'relationship';

                        fwrite($file_to_write, $this->tab . ' */');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . 'protected $' . lcfirst($field['field_name']) . ';');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->linebreak);
                        break;
                    case 'OneToMany':
                        $selected_entity_name   = explode('\\', $field['relationship_entity']);
                        $selected_entity_name   = end($selected_entity_name);
                        $target_entity          = $this->entity_name . $selected_entity_name;
                        $editable               = (isset($field['editable']) && $field['editable'] == '1') ? true : false;
                        $form_label             = (isset($field['editable_form_field_type'])) ? $field['editable_form_field_type'] : '';
                        $form_required          = (isset($field['editable_required']) && $field['editable_required'] == '1') ? ', required=true' : ', required=false';

                        $this->one_to_many_classes_to_create[] = array(
                                'field_name'    => $field['field_name'],
                                'bundle'        => $this->bundle,
                                'namespace'     => $this->namespace,
                                'owner'         => $this->entity_name,
                                'inverse'       => $field['relationship_entity'],
                                'entity_name'   => $target_entity, 
                                'mapped_by'     => $field['one_to_many_mapped_by'],
                                'editable'      => $editable,
                                'form_label'    => $form_label,
                                'form_required' => $form_required,
                            );

                        fwrite($file_to_write, $this->tab . '/**');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . ' * @ORM\OneToMany(targetEntity="' . $this->bundle . '\Entity\\' . $target_entity . '", orphanRemoval=true, mappedBy="' . $field['one_to_many_mapped_by'] . '", cascade={"persist","remove"})');
                        fwrite($file_to_write, $this->linebreak);

                        $field['field_type'] = 'embed';
                        $this->writeFieldDufAdminAnnotations($field);
                        $field['field_type'] = 'relationship';

                        fwrite($file_to_write, $this->tab . ' */');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->tab . 'protected $' . lcfirst($field['field_name']) . ';');
                        fwrite($file_to_write, $this->linebreak);
                        fwrite($file_to_write, $this->linebreak);
                        break;
                    case 'OneToOne':
                        // TO DO : OneToOne field type case
                        break;
                }
                break;

        }
    }

    private function writeOneToManyEntityClass($otm_entity)
    {
        $file_dir               = $this->getEntityDirectory($otm_entity['bundle']);
        $file_name              = $otm_entity['entity_name'] . '.php';
        $table_name             = strtolower($otm_entity['entity_name']);
        $this->otm_entity_file  = fopen($file_dir . $file_name, 'w');

        // php tags
        fwrite($this->otm_entity_file, '<?php ');
        fwrite($this->otm_entity_file, $this->linebreak);

        // namespace
        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, 'namespace ' . $otm_entity['namespace'] . ';');

        // write use
        $this->writeUse('doctrine', true);
        $this->writeUse('duf_admin_entity', true);
        $this->writeUse('duf_admin_annotations', true);

        // entity declaration
        $this->writeEntityDeclaration($otm_entity['entity_name'], $table_name, true);

        // class declaration
        $this->writeClassDeclaration($otm_entity['entity_name'], true);

        // write first field
        fwrite($this->otm_entity_file, $this->tab . '/**');
        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, $this->tab . ' * @ORM\ManyToOne(targetEntity="' . $otm_entity['namespace'] . '\\' . $otm_entity['owner'] . '", inversedBy="' . $otm_entity['field_name'] .'", cascade={"persist"})');
        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, $this->tab . ' * @ORM\JoinColumn(name="' . lcfirst($otm_entity['owner']) . '_id", referencedColumnName="id", nullable=true)');

        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, $this->tab . ' */');
        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, $this->tab . 'private $' . $otm_entity['mapped_by'] . ';');
        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, $this->linebreak);

        // close class declaration
        fwrite($this->otm_entity_file, $this->linebreak);
        fwrite($this->otm_entity_file, '}');

        fclose($this->otm_entity_file);

        // generate getters and setters
        $doctrine_generate_entities_response    = $this->doctrineGenerateEntities($this->bundle, $otm_entity['entity_name']);
    }

    private function writeFieldDufAdminAnnotations($field)
    {
        $indexable  = (isset($field['indexable']) && $field['indexable'] == '1') ? true : false;
        $editable   = (isset($field['editable']) && $field['editable'] == '1') ? true : false;

        // write DufAdmin's annotations if defined
        if ($indexable) {
            $relation_index             = ($field['field_type'] == 'entity') ? ', relation_index="' . $field['indexable_relation_index'] . '"' : '';

            fwrite($this->entity_file, $this->tab . ' * @Indexable(index_column=true, index_column_name="' . $field['indexable_column_name'] . '"' . $relation_index . ')');
            fwrite($this->entity_file, $this->linebreak);
        }

        if ($editable) {
            $relation_index = '';
            if (isset($field['relationship_type']) && isset($field['editable_relation_index'])) {
                if ($field['relationship_type'] == 'ManyToMany') {
                    $relation_index = ', relation_index="' . $field['editable_relation_index'] . '"';
                }
            }

            $filetype = '';
            if (isset($field['editable_file_type']) && !empty($field['editable_file_type'])) {
                $filetype = ', filetype="' . $field['editable_file_type'] . '"';
            }

            $editable_required          = (isset($field['editable_required']) && $field['editable_required'] == '1') ? ', required=true' : ', required=false';
            $editable_form_field_type   = $field['editable_form_field_type'];
            $editable_order             = $field['editable_order'];
            $editable_placeholder       = (isset($field['editable_placeholder']) && !empty($field['editable_placeholder'])) ? ', placeholder="' . $field['editable_placeholder'] . '"' : '';
            $number_type                = ($field['field_type'] == 'integer' || $field['field_type'] == 'float') ? ', number_type="' . $field['field_type'] . '"' : '' ;

            fwrite($this->entity_file, $this->tab . ' * @Editable(is_editable=true, label="' . $field['editable_label'] . '"' . $editable_required . ', type="' . $editable_form_field_type . '", order=' . $editable_order . $editable_placeholder . $relation_index . $filetype . $number_type . ')');
            fwrite($this->entity_file, $this->linebreak);
        }
    }

    private function getEntitiesAndBundles()
    {
        $meta       = $this->getDoctrine()->getManager()->getMetadataFactory()->getAllMetadata();
        $bundles    = array();

        foreach ($meta as $m) {
            $namespace      = $m->namespace;
            $name           = str_replace($namespace . '\\', '', $m->getName());
            $entity_pieces  = explode('\\', $namespace);
            $bundle         = '';

            foreach ($entity_pieces as $entity_piece) {
                if (strpos($entity_piece, 'Bundle') !== false) {
                    $bundle = $entity_piece;

                    if (!in_array($bundle, $bundles)) {
                        $bundles[] = $bundle;
                    }
                }
            }

            $entities[] = array(
                    'name'          => $name,
                    'namespace'     => $namespace,
                    'bundle'        => $bundle,
                    'repository'    => $m->customRepositoryClassName,
                );
        }

        return array(
                'entities'      => $entities,
                'bundles'       => $bundles,
            );
    }

    private function getEntityDirectory($bundle)
    {
        $base_dir = '../src/';

        if (is_dir($base_dir . $bundle)) {
            return $base_dir . $bundle . '/Entity/';
        }
        else {
            preg_match_all('/((?:^|[A-Z])[a-z]+)/',$bundle, $matches);

            foreach ($matches as $match_array) {
                foreach ($match_array as $bundle_part) {
                    $base_dir = $base_dir . $bundle_part;
                    if (is_dir($base_dir)) {
                        return $base_dir . '/Entity/';
                    }
                }
            }
        }

        return null;
    }

    private function getEntityContent()
    {

    }

    private function getFieldTypes($selected_field_type = null)
    {
        $entities_and_bundles   = $this->getEntitiesAndBundles();
        $entities               = array();

        foreach ($entities_and_bundles['entities'] as $_entity) {
            $entities[] = array(
                    'name'      => $_entity['namespace'] . '\\' . $_entity['name'],
                );
        }

        $field_types = array(
                array(
                        'name'      => 'text',
                        'label'     => 'Text',
                        'options'   => array(
                                array(
                                    'name'      => 'length',
                                    'label'     => 'Length',
                                    'required'  => true,
                                    'type'      => 'number',
                                ),
                            ),
                    ),
                array(
                        'name'  => 'date',
                        'label' => 'Date',
                    ),
                array(
                        'name'  => 'datetime',
                        'label' => 'DateTime',
                    ),
                array(
                        'name'  => 'boolean',
                        'label' => 'Boolean',
                    ),
                array(
                        'name'  => 'integer',
                        'label' => 'Integer',
                    ),
                array(
                        'name'  => 'float',
                        'label' => 'Float',
                    ),
                array(
                        'name'      => 'relationship',
                        'label'     => 'Relationship',
                        'options'   => array(
                                array(
                                    'name'      => 'relationship_type',
                                    'label'     => 'Relationship Type',
                                    'required'  => true,
                                    'type'      => 'select',
                                    'choices'   => array(
                                            array(
                                                'name' => 'ManyToOne',
                                            ),
                                            array(
                                                'name' => 'ManyToMany',
                                            ),
                                            array(
                                                'name' => 'OneToMany',
                                            ),
                                            array(
                                                'name' => 'OneToOne',
                                            ),
                                        ),
                                ),
                                array(
                                    'name'      => 'relationship_entity',
                                    'label'     => 'Entity',
                                    'required'  => true,
                                    'type'      => 'select',
                                    'choices'   => $entities,
                                ),
                            ),
                    ),
            );

        if (null !== $selected_field_type) {
            foreach ($field_types as $field_type) {
                if ($selected_field_type == $field_type['name']) {
                    return $field_type;
                }
            }
        }

        return $field_types;
    }
}
