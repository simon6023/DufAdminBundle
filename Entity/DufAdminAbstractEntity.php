<?php

namespace Duf\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class DufAdminAbstractEntity
{
    /** @ORM\prePersist */
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTime();
    }

    /** @ORM\preUpdate */
    public function setUpdatedAtValue()
    {
        $this->updated_at = new \DateTime();
    }
}