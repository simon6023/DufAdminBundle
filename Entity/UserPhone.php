<?php
namespace Duf\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
* @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\DufAdminRepository")
*/
class UserPhone extends DufAdminEntity
{
     /**
     * @ORM\ManyToOne(targetEntity="Duf\AdminBundle\Entity\User", inversedBy="phonesNbrs", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
     private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneType", type="string", length=50, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Type")
     * @EditableAnnotation(is_editable=true, label="Type", required=false, type="text", order=1, placeholder="Type")
     */
    private $phoneType;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneNbr", type="string", length=100)
     * @IndexableAnnotation(index_column=true, index_column_name="Number")
     * @EditableAnnotation(is_editable=true, label="Number", required=false, type="text", order=2, placeholder="Number")
     */
    private $phoneNbr;

    /**
     * Set phoneType
     *
     * @param string $phoneType
     *
     * @return UserPhone
     */
    public function setPhoneType($phoneType)
    {
        $this->phoneType = $phoneType;

        return $this;
    }

    /**
     * Get phoneType
     *
     * @return string
     */
    public function getPhoneType()
    {
        return $this->phoneType;
    }

    /**
     * Set phoneNbr
     *
     * @param string $phoneNbr
     *
     * @return UserPhone
     */
    public function setPhoneNbr($phoneNbr)
    {
        $this->phoneNbr = $phoneNbr;

        return $this;
    }

    /**
     * Get phoneNbr
     *
     * @return string
     */
    public function getPhoneNbr()
    {
        return $this->phoneNbr;
    }

    /**
     * Set user
     *
     * @param \Duf\AdminBundle\Entity\User $user
     *
     * @return UserPhone
     */
    public function setUser(\Duf\AdminBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Duf\AdminBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
