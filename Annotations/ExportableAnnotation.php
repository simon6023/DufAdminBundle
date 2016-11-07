<?php

namespace Duf\AdminBundle\Annotations;

/**
 * Annotation for exportable entities in DufAdminBundle
 *
 * @author Simon Duflos <simon.duflos@gmail.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class ExportableAnnotation
{

    /**
     * Parameter is_exportable
     *
     * @var boolean
     */
    public $is_exportable;

   /**
     * Parameter label
     *
     * @var string
     */
    public $label;

   /**
     * Parameter type
     *
     * @var string
     */
    public $type;

   /**
     * Parameter relation_field
     *
     * @var string
     */
    public $relation_field;
}