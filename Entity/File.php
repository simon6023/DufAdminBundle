<?php

namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * File
 *
 * @ORM\Table(name="file")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\DufAdminRepository")
 */
class File extends DufAdminEntity
{
    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     * @Assert\File()
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="filetype", type="string", length=50)
     */
    private $filetype;

    /**
     * @var string
     *
     * @ORM\Column(name="extension", type="string", length=10)
     */
    private $extension;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var float
     *
     * @ORM\Column(name="filesize", type="float")
     */
    private $filesize;

    /**
     * @var string
     *
     * @ORM\Column(name="crop", type="string", length=50, nullable=true)
     */
    private $crop;

    /**
     * @var integer
     *
     * @ORM\Column(name="width", type="integer", nullable=true)
     */
    private $width;

    /**
     * @var integer
     *
     * @ORM\Column(name="height", type="integer", nullable=true)
     */
    private $height;

    /**
     * @ORM\OneToMany(targetEntity="Duf\AdminBundle\Entity\FileMetadata", orphanRemoval=true, mappedBy="file", cascade={"persist","remove"})
     * @EditableAnnotation(is_editable=true, label="Metadatas", required=false, type="embed")
     */
     private $metadatas;

     public function __toString()
     {
        return $this->filename;
     }

    /**
     * Set file
     *
     * @param string $file
     *
     * @return File
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return File
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
     * Set filetype
     *
     * @param string $filetype
     *
     * @return File
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;

        return $this;
    }

    /**
     * Get filetype
     *
     * @return string
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * Set extension
     *
     * @param string $extension
     *
     * @return File
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return File
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
     * Set filesize
     *
     * @param float $filesize
     *
     * @return File
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filesize
     *
     * @return float
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set crop
     *
     * @param string $crop
     *
     * @return File
     */
    public function setCrop($crop)
    {
        $this->crop = $crop;

        return $this;
    }

    /**
     * Get crop
     *
     * @return string
     */
    public function getCrop()
    {
        return $this->crop;
    }

    /**
     * Set width
     *
     * @param integer $width
     *
     * @return File
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     *
     * @return File
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->metadatas = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add metadata
     *
     * @param \Duf\AdminBundle\Entity\FileMetadata $metadata
     *
     * @return File
     */
    public function addMetadata(\Duf\AdminBundle\Entity\FileMetadata $metadata)
    {
        $this->metadatas[] = $metadata;

        return $this;
    }

    /**
     * Remove metadata
     *
     * @param \Duf\AdminBundle\Entity\FileMetadata $metadata
     */
    public function removeMetadata(\Duf\AdminBundle\Entity\FileMetadata $metadata)
    {
        $this->metadatas->removeElement($metadata);
    }

    /**
     * Get metadatas
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMetadatas()
    {
        return $this->metadatas;
    }
}
