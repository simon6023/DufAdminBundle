<?php

namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Duf\AdminBundle\Entity\DufAdminEntity;
use Duf\AdminBundle\Model\DufAdminUserInterface;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * Task
 *
 * @ORM\Table(name="task")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\TaskRepository")
 */
class Task extends DufAdminEntity implements DufAdminUserInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @IndexableAnnotation(index_column=true, index_column_name="Title")
     * @EditableAnnotation(is_editable=true, label="Title", required=true, type="text", order=1, placeholder="Write your title")
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="progress", type="integer", nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Progress", suffix=" %")
     * @EditableAnnotation(is_editable=true, label="Progress (%)", required=false, type="number", order=2, number_type="integer")
     */
    private $progress;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @EditableAnnotation(is_editable=true, label="Description", required=true, type="textarea", order=3)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Duf\AdminBundle\Model\DufAdminUserInterface")
     * @ORM\JoinColumn(nullable=false)
     * @EditableAnnotation(is_editable=true, label="User", required=false, type="entity_hidden", class="user_entity", hidden_value="current_user", order=4)
     * @var DufAdminUserInterface
     */
     private $user;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Task
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set progress
     *
     * @param integer $progress
     *
     * @return Task
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Task
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set user
     *
     * @param \Duf\AdminBundle\Model\DufAdminUserInterface $user
     *
     * @return Task
     */
    public function setUser(\Duf\AdminBundle\Model\DufAdminUserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Duf\AdminBundle\Model\DufAdminUserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}
