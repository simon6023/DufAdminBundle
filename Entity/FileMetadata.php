<?php

namespace Duf\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * FileMetadata
 *
 * @ORM\Table(name="file_metadata")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\DufAdminRepository")
 */
class FileMetadata extends DufAdminEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="meta_key", type="string", length=255)
     * @IndexableAnnotation(index_column=true, index_column_name="Key")
     * @EditableAnnotation(is_editable=true, label="Key", required=true, type="text", order=1, placeholder="Key")
     */
    private $metaKey;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_value", type="string", length=255)
     * @IndexableAnnotation(index_column=true, index_column_name="Value")
     * @EditableAnnotation(is_editable=true, label="Value", required=true, type="text", order=2, placeholder="Value")
     */
    private $metaValue;

     /**
     * @ORM\ManyToOne(targetEntity="Duf\AdminBundle\Entity\File", inversedBy="metadatas", cascade={"persist"})
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=false)
     * @ORM\OrderBy({"id" = "ASC"})
     * @IndexableAnnotation(index_column=false)
     * @EditableAnnotation(is_editable=true, label="", required=true, type="hidden", order=3, placeholder="")
     */
     private $file;

    /**
     * Set metaKey
     *
     * @param string $metaKey
     *
     * @return FileMetadata
     */
    public function setMetaKey($metaKey)
    {
        $this->metaKey = $metaKey;

        return $this;
    }

    /**
     * Get metaKey
     *
     * @return string
     */
    public function getMetaKey()
    {
        return $this->metaKey;
    }

    /**
     * Set metaValue
     *
     * @param string $metaValue
     *
     * @return FileMetadata
     */
    public function setMetaValue($metaValue)
    {
        $this->metaValue = $metaValue;

        return $this;
    }

    /**
     * Get metaValue
     *
     * @return string
     */
    public function getMetaValue()
    {
        return $this->metaValue;
    }

    /**
     * Set file
     *
     * @param \Duf\AdminBundle\Entity\File $file
     *
     * @return FileMetadata
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
}
