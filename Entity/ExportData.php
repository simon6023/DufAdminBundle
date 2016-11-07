<?php

namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExportData
 *
 * @ORM\Table(name="export_data")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\ExportDataRepository")
 */
class ExportData
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="entityName", type="string", length=255, nullable=true)
     */
    private $entityName;

    /**
     * @var array
     *
     * @ORM\Column(name="items", type="json_array")
     */
    private $items;

    /**
     * @var string
     *
     * @ORM\Column(name="format", type="string", length=10)
     */
    private $format;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set entityName
     *
     * @param string $entityName
     *
     * @return ExportData
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get entityName
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Set items
     *
     * @param array $items
     *
     * @return ExportData
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Get items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set format
     *
     * @param string $format
     *
     * @return ExportData
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
}

