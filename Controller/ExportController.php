<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Annotations\AnnotationReader;

use Duf\AdminBundle\Entity\ExportData;

class ExportController extends Controller
{
    public function generateAction($format, $entity_name, Request $request)
    {
        $items      = $request->get('items');

        if (null === $items)
            return new Response('no_items_selected', 500);

        $em             = $this->getDoctrine()->getManager();
        $items          = explode(',', $items);
        $qb             = $em->createQueryBuilder();
        $entities       = $qb
                             ->select('u')
                             ->from($entity_name, 'u')
                             ->where($qb->expr()->in('u.id', $items))
                             ->getQuery()
                             ->getResult()
                        ;

        $export_data    = new ExportData();
        $export_data->setItems($entities);
        $export_data->setEntityName($entity_name);
        $export_data->setFormat($format);

        $em->persist($export_data);
        $em->flush();

        return new Response($export_data->getId(), 200);
    }

    public function downloadAction($id)
    {
        $export_data        = $this->getDoctrine()->getRepository('DufAdminBundle:ExportData')->findOneById($id);

        if (empty($export_data))
            return new Response('export_data_not_found', 500);

        $entities           = $this->getDoctrine()->getRepository('DufAdminBundle:ExportData')->findByExportEntities($export_data->getEntityName(), $export_data->getItems());

        if (empty($entities))
            return new Response('empty_export_data', 500);

        $export_fields      = $this->getExportFieldsForEntity($export_data->getEntityName());
        $filename           = 'export_' . date('U') . '.' . $export_data->getFormat();
        $response           = $this->render('DufAdminBundle:Export:' . $export_data->getFormat() . '.html.twig',
                                array(
                                    'entities'          => $entities,
                                    'export_fields'     => $export_fields,
                                )
                            );

        $response->setStatusCode(200);

        $response           = $this->setContentType($response, $export_data->getFormat());

        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }

    private function setContentType($response, $format)
    {
        if ($format == 'csv') {
            $response->headers->set('Content-Type', 'text/csv;');
        }

        if ($format == 'xlsx') {
            $response->headers->set('Content-Type', 'application/vnd.ms-excel;');
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, charset=utf-8;');
        }

        return $response;
    }

    private function getExportFieldsForEntity($entity_name)
    {
        $entity_class           = $this->get('duf_admin.dufadminrouting')->getEntityClass($entity_name);
        $form_service           = $this->get('duf_admin.dufadminform');
        $annotationReader       = new AnnotationReader();
        $entity_properties      = $form_service->getEntityProperties($entity_name);
        $export_fields          = array();

        foreach ($entity_properties as $property_name) {
            if (!property_exists($entity_class, $property_name))
                continue;

            $reflectionClass        = new \ReflectionProperty($entity_class, $property_name);
            $annotations            = $annotationReader->getPropertyAnnotations($reflectionClass);

            foreach ($annotations as $annotation) {
                if ('Duf\AdminBundle\Annotations\ExportableAnnotation' !== get_class($annotation))
                    continue;

                $export_fields[$property_name]    = array(
                        'type'              => $annotation->type,
                        'label'             => $annotation->label,
                        'relation_field'    => $annotation->relation_field,
                    );
            }
        }

        return $export_fields;
    }
}