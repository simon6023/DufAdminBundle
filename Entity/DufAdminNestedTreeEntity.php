<?php
namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Duf\AdminBundle\Entity\DufAdminAbstractEntity;
use Duf\AdminBundle\Annotations\IndexableAnnotation;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class DufAdminNestedTreeEntity extends DufAdminAbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @IndexableAnnotation(index_column=true, index_column_name="Id", index_column_order=1)
     */
    public $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @IndexableAnnotation(index_column=true, index_column_name="Created At", index_column_order=2)
     */
    public $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Updated At", index_column_order=3)
     */
    public $updated_at;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    public $enabled;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    public $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     */
    public $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    public $rgt;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return User
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function getcreated_at()
    {
        return $this->created_at->format('d/m/Y');
    }

    public function getupdated_at()
    {
        return $this->updated_at->format('d/m/Y');
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return DufAdminNestedTreeEntity
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    public function getCategoryLevel()
    {
        return $this->lvl;
    }

    public function setLft($left)
    {
        $this->lft = $left;
    }

    public function setLvl($level)
    {
        $this->lvl = $level;
    }
}