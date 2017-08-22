<?php
namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\UserInterface;

use Duf\AdminBundle\Entity\DufAdminAbstractEntity;
use Duf\AdminBundle\Annotations\IndexableAnnotation;
use Duf\AdminBundle\Annotations\EditableAnnotation;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class DufAdminUser extends DufAdminAbstractEntity implements UserInterface, \Serializable
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
     * @var string
     *
     * @ORM\Column(name="form_token", type="string", length=255, nullable=true)
     */
    public $form_token;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Username")
     * @EditableAnnotation(is_editable=true, label="Username", required=true, type="text", order=1, placeholder="Username")
     */
    public $username;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=100, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Firstname")
     * @EditableAnnotation(is_editable=true, label="Firstname", required=false, type="text", order=2, placeholder="Firstname")
     */
    public $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=true)
     * @IndexableAnnotation(index_column=true, index_column_name="Lastname")
     * @EditableAnnotation(is_editable=true, label="Lastname", required=false, type="text", order=3, placeholder="Lastname")
     */
    public $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false, unique=true)
     * @EditableAnnotation(is_editable=true, label="Email", required=true, type="text", order=4, placeholder="Email")
     */
    public $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    public $last_login;

    /**
    * @ORM\Column(name="password", type="string", length=255, nullable=true)
    * @EditableAnnotation(is_editable=true, label="Password", required=false, type="password", order=10, placeholder="Password")
    */
    public $password;

    /**
    * @ORM\Column(name="salt", type="string", length=255, nullable=true)
    */
    public $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="redmine_username", type="string", length=100, nullable=true)
     * @EditableAnnotation(is_editable=true, label="Redmine Username", required=false, type="text", order=8, placeholder="Redmine Username")
     */
    public $redmine_username;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     * @EditableAnnotation(is_editable=true, label="Enabled", required=true, type="checkbox", order=5)
     */
    public $isActive;

    /**
     * @ORM\Column(name="optin_messages", type="boolean")
     * @EditableAnnotation(is_editable=true, label="Can receive messages", required=false, type="checkbox", order=9)
     */
    public $optinMessages;

    /**
     * @ORM\ManyToOne(targetEntity="Duf\AdminBundle\Entity\File")
     * @ORM\JoinColumn(nullable=true)
     * @EditableAnnotation(is_editable=true, label="Avatar", required=false, type="file", filetype="images", order=7)
     */
     public $avatar;

    /**
     * @ORM\ManyToMany(targetEntity="Duf\AdminBundle\Model\DufAdminUserRoleInterface", inversedBy="users")
     * @EditableAnnotation(is_editable=true, label="Roles", required=true, multiple=true, type="entity", order=6, relation_index="name")
     * @var DufAdminUserRoleInterface
     */
    public $roles;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->username;
    }

    /**
     * Get id
     *
     * @return integer
     */
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
     * Set formToken
     *
     * @param string $formToken
     *
     * @return User
     */
    public function setFormToken($formToken)
    {
        $this->form_token = $formToken;

        return $this;
    }

    /**
     * Get formToken
     *
     * @return string
     */
    public function getFormToken()
    {
        return $this->form_token;
    }

    public function eraseCredentials()
    {
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

    /**
     * Add role
     *
     * @param \Duf\AdminBundle\Model\DufAdminUserRoleInterface $role
     *
     * @return User
     */
    public function addRole(\Duf\AdminBundle\Model\DufAdminUserRoleInterface $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \Duf\AdminBundle\Model\DufAdminUserRoleInterface $role
     */
    public function removeRole(\Duf\AdminBundle\Model\DufAdminUserRoleInterface $role)
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
}
