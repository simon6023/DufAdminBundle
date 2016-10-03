<?php
namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * @ORM\Table(name="duf_admin_users")
 * @ORM\Entity(repositoryClass="Duf\AdminBundle\Entity\Repository\UserRepository")
 */
class User extends DufAdminEntity implements UserInterface, \Serializable
{
    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Username")
     * @EditableAnnotation(is_editable=true, label="Username", required=true, type="text", order=1, placeholder="Username")
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=100, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Firstname")
     * @EditableAnnotation(is_editable=true, label="Firstname", required=false, type="text", order=2, placeholder="Firstname")
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Lastname")
     * @EditableAnnotation(is_editable=true, label="Lastname", required=false, type="text", order=3, placeholder="Lastname")
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false, unique=true)
     * @EditableAnnotation(is_editable=true, label="Email", required=true, type="text", order=4, placeholder="Email")
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $last_login;

    /**
    * @ORM\Column(name="password", type="string", length=255, nullable=true)
    * @EditableAnnotation(is_editable=true, label="Password", required=false, type="password", order=10, placeholder="Password")
    */
    private $password;

    /**
    * @ORM\Column(name="salt", type="string", length=255, nullable=true)
    */
    private $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="redmine_username", type="string", length=100, nullable=true)
     * @EditableAnnotation(is_editable=true, label="Redmine Username", required=false, type="text", order=8, placeholder="Redmine Username")
     */
    private $redmine_username;

    /**
     * @ORM\ManyToMany(targetEntity="Duf\AdminBundle\Entity\UserRole", inversedBy="users")
     * @EditableAnnotation(is_editable=true, label="Roles", required=true, multiple=true, type="entity", order=6, relation_index="name")
     */
    private $roles;

    /**
     * @ORM\OneToMany(targetEntity="Duf\AdminBundle\Entity\UserPhone", orphanRemoval=true, mappedBy="user", cascade={"persist","remove"})
     * @EditableAnnotation(is_editable=true, label="Phone Numbers", required=false, type="embed")
     */
     private $phonesNbrs;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     * @EditableAnnotation(is_editable=true, label="Enabled", required=true, type="checkbox", order=5)
     */
    private $isActive;

    /**
     * @ORM\Column(name="optin_messages", type="boolean")
     * @EditableAnnotation(is_editable=true, label="Can receive messages", required=false, type="checkbox", order=9)
     */
    private $optinMessages;

    /**
     * @ORM\ManyToOne(targetEntity="Duf\AdminBundle\Entity\File")
     * @ORM\JoinColumn(nullable=true)
     * @EditableAnnotation(is_editable=true, label="Avatar", required=false, type="file", filetype="images", order=7)
     */
     protected $avatar;

    public function __toString()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     *
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->last_login = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return md5(sha1(date('Ymd')));
        //return $this->salt;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add role
     *
     * @param \Duf\AdminBundle\Entity\UserRole $role
     *
     * @return User
     */
    public function addRole(\Duf\AdminBundle\Entity\UserRole $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \Duf\AdminBundle\Entity\UserRole $role
     */
    public function removeRole(\Duf\AdminBundle\Entity\UserRole $role)
    {
        $this->roles->removeElement($role);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles->toArray();
    }

    /**
     * Add phonesNbr
     *
     * @param \Duf\AdminBundle\Entity\UserPhone $phonesNbr
     *
     * @return User
     */
    public function addPhonesNbr(\Duf\AdminBundle\Entity\UserPhone $phonesNbr)
    {
        $this->phonesNbrs[] = $phonesNbr;

        return $this;
    }

    /**
     * Remove phonesNbr
     *
     * @param \Duf\AdminBundle\Entity\UserPhone $phonesNbr
     */
    public function removePhonesNbr(\Duf\AdminBundle\Entity\UserPhone $phonesNbr)
    {
        $this->phonesNbrs->removeElement($phonesNbr);
    }

    /**
     * Get phonesNbrs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhonesNbrs()
    {
        return $this->phonesNbrs;
    }

    /**
     * Set avatar
     *
     * @param \Duf\AdminBundle\Entity\File $avatar
     *
     * @return User
     */
    public function setAvatar(\Duf\AdminBundle\Entity\File $avatar = null)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return \Duf\AdminBundle\Entity\File
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set redmineUsername
     *
     * @param string $redmineUsername
     *
     * @return User
     */
    public function setRedmineUsername($redmineUsername)
    {
        $this->redmine_username = $redmineUsername;

        return $this;
    }

    /**
     * Get redmineUsername
     *
     * @return string
     */
    public function getRedmineUsername()
    {
        return $this->redmine_username;
    }

    /**
     * Set optinMessages
     *
     * @param boolean $optinMessages
     *
     * @return User
     */
    public function setOptinMessages($optinMessages)
    {
        $this->optinMessages = $optinMessages;

        return $this;
    }

    /**
     * Get optinMessages
     *
     * @return boolean
     */
    public function getOptinMessages()
    {
        return $this->optinMessages;
    }
}
