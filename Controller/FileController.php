<?php

namespace Duf\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileController extends Controller
{
    public function indexAction($filetype, $page = 1, Request $request)
    {
    	$limit		= $this->get('duf_admin.dufadminconfig')->getDufAdminConfig('file_system_items_per_page');
    	$start 		= ($page * $limit) - $limit;
        $entities 	= $this->getDoctrine()->getRepository('DufAdminBundle:File')->findByFileType($filetype, $start, $limit);
        $template 	= 'DufAdminBundle:File:index.html.twig';

        if ($request->isXmlHttpRequest()) {
        	$template = 'DufAdminBundle:File:index-list.html.twig';
        }

        $parent_entity_id = null;
        if (null !== $request->get('select_file_parent_id')) {
            $parent_entity_id = $request->get('select_file_parent_id');
        }

        $parent_entity_class = null;
        if (null !== $request->get('select_file_parent_entity_class')) {
            $parent_entity_class = str_replace('/', '\\', $request->get('select_file_parent_entity_class'));
        }

        $parent_entity_property = null;
        if (null !== $request->get('select_file_parent_property')) {
            $parent_entity_property = str_replace('/', '\\', $request->get('select_file_parent_property'));
        }

        return $this->render($template, array(
                'entities'                  => $entities,
                'filetype'                  => $filetype,
                'page'                      => $page,
                'parent_entity_id'          => $parent_entity_id,
                'parent_entity_class'       => $parent_entity_class,
                'parent_entity_property'    => $parent_entity_property,
            )
        );
    }

    public function ajaxUploadAction($filetype, Request $request)
    {
    	$files 			= $request->files;
    	$file_service 	= $this->get('duf_admin.dufadminfile');

    	if (!empty($files)) {
    		$em 			= $this->getDoctrine()->getManager();
    		$upload_done 	= false;

    		foreach ($files as $file_input) {
    			foreach ($file_input as $file) {
	    			$upload_file 	= $file_service->createFileEntity($file, $filetype);
	    			if (null !== $upload_file) {
	    				// move file to upload dir
	    				if ($file->move($upload_file->getPath(), $upload_file->getFilename())) {
	    					// save file entity	    					
	    					$em->persist($upload_file);
	    					$em->flush();

	    					$upload_done 	= true;
	    				}
	    			}
    			}
    		}

    		if ($upload_done) {
    			return new JsonResponse(
                        array(
                            'id'            => $upload_file->getId(),
                            'filename'      => $upload_file->getFilename(),
                            'path'          => $upload_file->getPath(),
                        )
                    );
    		}
    	}

    	return new JsonResponse('error');
    }

    public function deleteFileAction($file_id)
    {
    	$file 	= $this->getDoctrine()->getRepository('DufAdminBundle:File')->findOneById($file_id);
    	if (!empty($file)) {
    		$em = $this->getDoctrine()->getManager();
    		$em->remove($file);
    		$em->flush();

    		return new Response('ok', 200);
    	}

    	return new Response('error', 500);
    }

    public function getFileAction($file_id)
    {
        $file = $this->getDoctrine()->getRepository('DufAdminBundle:File')->findOneById($file_id);
        if (!empty($file)) {
            return new JsonResponse(
                    array(
                        'id'                => $file->getId(),
                        'filename'          => $file->getFilename(),
                        'filetype'          => $file->getFiletype(),
                        'path'              => $file->getPath(),
                    )
                );
        }

        return new Response('error', 500);
    }
}
