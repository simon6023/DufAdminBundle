<?php

namespace Duf\AdminBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;

class EntityToIdTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $class;

    public function __construct(ObjectManager $objectManager, $class)
    {
        $this->objectManager    = $objectManager;
        $this->class            = $class;
    }

    public function transform($entity)
    {
        if (null === $entity) {
            return;
        }

        if (is_int($entity))
            return $entity;

        return $entity->getId();
    }

    public function reverseTransform($id)
    {
        if (!$id || null === $this->class) {
            return null;
        }

        $entity = $this->objectManager
                       ->getRepository($this->class)
                       ->find($id);

        if (null === $entity) {
            throw new TransformationFailedException();
        }

        return $entity;
    }

    public function getClass()
    {
        return $this->class;
    }
}