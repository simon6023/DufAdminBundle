<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TreeController extends Controller
{
    public function getTreeAction($entity_name = null, Request $request)
    {
        $repo           = $this->getDoctrine()->getRepository($entity_name);
        $parent_id      = (null !== $request->get('id') && $request->get('id') !== '0') ? $request->get('id') : null;
        $parent         = (null !== $parent_id) ? $repo->findOneById($parent_id) : null;
        $categories     = (null !== $parent) ? $repo->getChildren($parent, true) : $repo->getRootNodes();
        $tree           = $this->formatTreeForHtml($categories);

        return new JsonResponse($tree);
    }

    public function saveTreeAction($entity_name, $action, $node_id = null, Request $request)
    {
        $em             = $this->getDoctrine()->getManager();
        $repo           = $this->getDoctrine()->getRepository($entity_name);
        $entity_class   = $this->get('duf_admin.dufadminrouting')->getEntityClass($entity_name);

        if ($action === 'create') {
            $parent     = ((int)$request->get('parent') !== 0) ? $this->getDoctrine()->getRepository($entity_name)->findOneById($request->get('parent')) : null;

            $position   = (null !== $request->get('position') && $request->get('position') !== '0') ? $request->get('position') : null;
            $related    = (null !== $request->get('related') && $request->get('related') !== '0') ? $request->get('related') : null;

            $entity     = new $entity_class;
            $entity->setTitle($request->get('name'));
            $entity->setParent($parent);
            $entity->setEnabled(true);

            if (null !== $position && null !== $related && null !== $parent) {
                $related_entity     = $repo->findOneById($related);

                if ($position === 'before') {
                    $repo->persistAsPrevSiblingOf($entity, $related_entity);
                }
                elseif ($position === 'after') {
                    $repo->persistAsNextSiblingOf($entity, $related_entity);
                }
                elseif ($position === 'firstChild') {
                    $repo->persistAsFirstChildOf($entity, $related_entity);
                }
                elseif ($position === 'lastChild') {
                    $repo->persistAsLastChildOf($entity, $related_entity);
                }
            }
            else {
                $em->persist($entity);
            }

            if (null !== $parent) {
                $repo->reorder($parent);
            }

            $em->flush();
        }
        elseif ($action == 'update') {
            $entity    = $repo->findOneById($node_id);
            $entity->setTitle($request->get('name'));

            $em->persist($entity);
            $em->flush();
        }

        $category       = (isset($entity)) ? $this->getCategoryNode($entity) : null;
        return new JsonResponse($category);
    }

    public function removeTreeAction($entity_name, $node_id)
    {
        $em             = $this->getDoctrine()->getManager();
        $repo           = $this->getDoctrine()->getRepository($entity_name);
        $category       = $repo->findOneById($node_id);

        $repo->removeFromTree($category);
        $em->clear();

        return new JsonResponse();
    }

    public function moveTreeAction($entity_name, $node_id, $direction)
    {
        $em             = $this->getDoctrine()->getManager();
        $repo           = $this->getDoctrine()->getRepository($entity_name);
        $category       = $repo->findOneById($node_id);

        if ($direction == 'up') {
            $repo->moveUp($category, 1);
        }
        elseif ($direction == 'down') {
            $repo->moveDown($category, 1);
        }

        $em->clear();

        return new JsonResponse();
    }

    private function formatTreeForHtml($categories)
    {
        $nodes      = array();

        if (empty($categories)) {
            $nodes['nodes'] = array();
            return $nodes;
        }

        foreach ($categories as $category) {
            $nodes['nodes'][] = $this->getCategoryNode($category);
        }

        return $nodes;
    }

    private function getCategoryNode($entity)
    {
        $category   = array(
                'id'        => $entity->getId(),
                'level'     => $entity->getCategoryLevel(),
                'name'      => $entity->getTitle(),
                'type'      => 'default',
            );

        return $category;
    }
}
