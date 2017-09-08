<?php

namespace Duf\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class DufAdminAbstractEntity
{
    /** @ORM\PrePersist */
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTime();
    }

    /** @ORM\PreUpdate */
    public function setUpdatedAtValue()
    {
        $this->updated_at = new \DateTime();
    }
}