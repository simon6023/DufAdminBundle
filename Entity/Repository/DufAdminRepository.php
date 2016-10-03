<?php

namespace Duf\Bundle\AdminBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DufAdminRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DufAdminRepository extends EntityRepository
{
    public function findByFormToken($entity_name, $token)
    {
        return $this->_em->createQueryBuilder()
                         ->select('u')
                         ->from($entity_name, 'u')
                         ->where('u.form_token = :token')
                         ->setParameter('token', $token)
                         ->getQuery()
                         ->getResult();
    }

    public function findByParentEntityId($entity_name, $child_entity_property, $parent_entity_id)
    {
        return $this->_em->createQueryBuilder()
                         ->select('u')
                         ->from($entity_name, 'u')
                         ->where('u.' . $child_entity_property . ' = :value')
                         ->setParameter('value', $parent_entity_id)
                         ->getQuery()
                         ->getResult();
    }

    public function findByFileType($filetype, $start, $limit)
    {   
        return $this->_em->createQueryBuilder()
                         ->select('file')
                         ->from('DufAdminBundle:File', 'file')
                         ->where('file.filetype = :filetype')
                         ->setParameter('filetype', $filetype)
                         ->orderBy('file.created_at', 'DESC')
                         ->setFirstResult($start)
                         ->setMaxResults($limit)
                         ->getQuery()
                         ->getResult();
    }
}
