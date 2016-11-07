<?php

namespace Duf\AdminBundle\Annotations;

/**
 * Annotation for editable entities in DufAdminBundle
 *
 * @author Simon Duflos <simon.duflos@gmail.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class EditableAnnotation
{

    /**
     * Parameter is_editable
     *
     * @var boolean
     */
    public $is_editable;

    /**
     * Parameter empty_value
     *
     * @var boolean
     */
    public $empty_value;

    /**
     * Parameter required
     *
     * @var boolean
     */
    public $required;

    /**
     * Parameter multiple
     *
     * @var boolean
     */
    public $multiple;

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
     * Parameter filetype
     *
     * @var string
     */
    public $filetype;

    /**
     * Parameter order
     *
     * @var integer
     */
    public $order;

   /**
     * Parameter placeholder
     *
     * @var string
     */
    public $placeholder;

    /**
     * Parameter relation_index
     *
     * @var string
     */
    public $relation_index;

    /**
     * Parameter number_type
     *
     * @var string
     */
    public $number_type;

    /**
     * Parameter hidden_value
     *
     * @var string
     */
    public $hidden_value;

    /**
     * Parameter class
     *
     * @var string
     */
    public $class;

    /**
     * Parameter choices
     *
     * @var array
     */
    public $choices;
}