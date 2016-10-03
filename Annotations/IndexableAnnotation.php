<?php

namespace Duf\Bundle\AdminBundle\Annotations;

/**
 * Annotation for indexable entities in DufAdminBundle
 *
 * @author Simon Duflos <simon.duflos@gmail.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class IndexableAnnotation
{

    /**
     * Parameter index_column
     *
     * @var boolean
     */
    public $index_column;

    /**
     * Parameter index_column_name
     *
     * @var string
     */
    public $index_column_name;

    /**
     * Parameter index_column_order
     *
     * @var integer
     */
    public $index_column_order;

    /**
     * Parameter relation_index
     *
     * @var string
     */
    public $relation_index;

    /**
     * Parameter suffix
     *
     * @var string
     */
    public $suffix;

    /**
     * Parameter prefix
     *
     * @var string
     */
    public $prefix;

    /**
     * Parameter boolean_column
     *
     * @var boolean
     */
    public $boolean_column;
}