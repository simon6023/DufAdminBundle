<?php

namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * Language
 *
 * @ORM\Table(name="language")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\LanguageRepository")
 */
class Language extends DufAdminEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @IndexableAnnotation(index_column=true, index_column_name="Name")
     * @EditableAnnotation(is_editable=true, label="Name", required=true, type="text", order=1, placeholder="Name")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=5)
     * @IndexableAnnotation(index_column=true, index_column_name="Code")
     * @EditableAnnotation(is_editable=true, label="Code", required=true, type="text", order=2, placeholder="Code")
     */
    private $code;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     * @IndexableAnnotation(index_column=true, index_column_name="Enabled")
     * @EditableAnnotation(is_editable=true, label="Enabled", required=true, type="checkbox", order=3)
     */
    private $enabled;

    /**
     * @ORM\Column(name="isAdmin", type="boolean")
     * @IndexableAnnotation(index_column=true, index_column_name="Admin Language")
     * @EditableAnnotation(is_editable=true, label="Admin Language", required=false, type="checkbox", order=4)
     */
    private $isAdmin;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Language
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
     * Set code
     *
     * @param string $code
     *
     * @return Language
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return Language
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

    /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     *
     * @return Language
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return boolean
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }
}
