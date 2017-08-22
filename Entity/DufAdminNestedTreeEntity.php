<?php
namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Duf\AdminBundle\Entity\DufAdminAbstractEntity;
use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

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
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @IndexableAnnotation(index_column=true, index_column_name="Created At", index_column_order=2)
     */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Updated At", index_column_order=3)
     */
    private $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @IndexableAnnotation(index_column=true, index_column_name="Title")
     * @EditableAnnotation(is_editable=true, label="Title", required=true, type="text", order=1, placeholder="Write your title")
     */
    private $title;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    private $rgt;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return DufAdminNestedTreeEntity
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
     * @return DufAdminNestedTreeEntity
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
     * Set title
     *
     * @param string $title
     *
     * @return DufAdminNestedTreeEntity
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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