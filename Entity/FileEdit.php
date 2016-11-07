<?php

namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * FileEdit
 *
 * @ORM\Table(name="file_edit")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\DufAdminRepository")
 */
class FileEdit extends DufAdminEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_entity", type="string", length=255)
     */
    private $parent_entity;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_entity_id", type="string", length=255)
     */
    private $parent_entity_id;

    /**
     * @var string
     *
     * @ORM\Column(name="property", type="string", length=255)
     */
    private $property;

    /**
     * @var array
     *
     * @ORM\Column(name="edit_data", type="json_array", nullable=false)
     */
    private $edit_data;

    /**
     * @ORM\ManyToOne(targetEntity="Duf\AdminBundle\Entity\File")
     * @ORM\JoinColumn(nullable=false)
     */
     protected $file;

    public function __toString()
    {
        return $this->filename;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return FileEdit
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return FileEdit
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set parentEntity
     *
     * @param string $parentEntity
     *
     * @return FileEdit
     */
    public function setParentEntity($parentEntity)
    {
        $this->parent_entity = $parentEntity;

        return $this;
    }

    /**
     * Get parentEntity
     *
     * @return string
     */
    public function getParentEntity()
    {
        return $this->parent_entity;
    }

    /**
     * Set parentEntityId
     *
     * @param string $parentEntityId
     *
     * @return FileEdit
     */
    public function setParentEntityId($parentEntityId)
    {
        $this->parent_entity_id = $parentEntityId;

        return $this;
    }

    /**
     * Get parentEntityId
     *
     * @return string
     */
    public function getParentEntityId()
    {
        return $this->parent_entity_id;
    }

    /**
     * Set file
     *
     * @param \Duf\AdminBundle\Entity\File $file
     *
     * @return FileEdit
     */
    public function setFile(\Duf\AdminBundle\Entity\File $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return \Duf\AdminBundle\Entity\File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set editData
     *
     * @param array $editData
     *
     * @return FileEdit
     */
    public function setEditData($editData)
    {
        $this->edit_data = $editData;

        return $this;
    }

    /**
     * Get editData
     *
     * @return array
     */
    public function getEditData()
    {
        return $this->edit_data;
    }

    /**
     * Set property
     *
     * @param string $property
     *
     * @return FileEdit
     */
    public function setProperty($property)
    {
        $this->property = $property;

        return $this;
    }

    /**
     * Get property
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }
}
