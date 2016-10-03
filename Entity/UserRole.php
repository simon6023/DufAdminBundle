<?php
namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * UserRole
 *
 * @ORM\Entity
 * @ORM\Table(name="duf_admin_user_role")
 */
class UserRole extends DufAdminEntity implements RoleInterface, \Serializable
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=60)
     * @IndexableAnnotation(index_column=true, index_column_name="Name")
     * @EditableAnnotation(is_editable=true, label="Name", required=true, type="text", order=1, placeholder="Role name")
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Duf\AdminBundle\Entity\User", mappedBy="roles")
     */
    private $users;


    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserRole
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

    public function serialize() {
        return serialize(array($this->getName()));
    }

    public function unserialize($serialized) {
        $arr = unserialize($serialized);
        $this->name = $arr[0];
    }

    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->name;
    }
}