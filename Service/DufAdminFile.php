<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\ORM\EntityManager as EntityManager;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Imagick\Imagine as Imagick;
use Imagine\Image\Point;
use Imagine\Image\Box;

use Duf\AdminBundle\Entity\File;
use Duf\AdminBundle\Entity\FileEdit;

class DufAdminFile
{
    private $em;
    private $container;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em           = $em;
        $this->container    = $container;
    }

    public function getFileExtension($file)
    {
        $original_name = $file->getClientOriginalName();
        return pathinfo($original_name,PATHINFO_EXTENSION);
    }

    public function getUploadDir($filetype)
    {
        return $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('upload_dir') . strtolower($filetype);
    }

    public function getFileInfos($file)
    {
        $fileSize       = filesize($file);
        $fileSizeInfos  = getimagesize($file);

        return array(
                'size'      => $fileSize,
                'width'     => $fileSizeInfos[0],
                'height'    => $fileSizeInfos[1]
            );
    }

    /**
     * Create a File entity (used during upload actions)
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file UploadedFile object from request
     * @return \Duf\AdminBundle\Entity\File
    */
    public function createFileEntity($file, $filetype)
    {
        if (null !== $file) {
            $upload_dir     = $this->getUploadDir($filetype);
            $extension      = $this->getFileExtension($file);
            $filename       = md5(uniqid()).'.'.$extension;
            $file_infos     = $this->getFileInfos($file);

            if ($this->isAllowedExtension($filetype, $extension)) {
                $file_entity    = new File();
                $file_entity->setFile($file);
                $file_entity->setFilename($filename);
                $file_entity->setFiletype($filetype);
                $file_entity->setExtension($extension);
                $file_entity->setPath($upload_dir);
                $file_entity->setFilesize($file_infos['size']);

                if (isset($file_infos['height']) && !empty($file_infos['height']) && (int)$file_infos['height'] > 0) {
                    $file_entity->setHeight($file_infos['height']);
                }

                if (isset($file_infos['width']) && !empty($file_infos['width']) && (int)$file_infos['width'] > 0) {
                    $file_entity->setWidth($file_infos['width']);
                }

                return $file_entity;
            }
        }

        return null;
    }

    /**
     * Get cleaned path of file (remove "web" from path)
     *
     * @param \Duf\AdminBundle\Entity\File $file_entity Doctrine File entity
     * @return string
    */
    public function getWebPath($file_entity)
    {
        $web_path       = str_replace('../web', '', $file_entity->getPath());
        $web_path       = $web_path . '/' . $file_entity->getFilename();

        return $web_path;
    }

    /**
     * Get array of allowed filetypes
     *
     * @return array
    */
    public function getFileTypes()
    {
        return array(
                'images'        => 'Image',
                'videos'        => 'Video',
                'documents'     => 'Document',
            );
    }

    public function createFileEditEntity($file, $parent_entity, $parent_entity_id, $property, $edit_data)
    {
        $cache_path     = $this->getCachePath($parent_entity);
        $new_filename   = $this->getNewFilename($file, $parent_entity, $parent_entity_id, false);
        $file_edit      = new FileEdit();

        // remove previous FileEdit entities
        $this->removeFileEditEntities($file, $parent_entity, $parent_entity_id);

        $file_edit->setFile($file);
        $file_edit->setParentEntity($parent_entity);
        $file_edit->setParentEntityId($parent_entity_id);
        $file_edit->setEditData($edit_data);
        $file_edit->setPath($cache_path);
        $file_edit->setFilename($new_filename);
        $file_edit->setCreatedAt(new \DateTime());
        $file_edit->setProperty($property);

        $this->em->persist($file_edit);
        $this->em->flush();

        return $file_edit;
    }

    public function createFileEdit($file_edit)
    {
        if (!is_object($file_edit))
            $file_edit      = $this->em->getRepository('DufAdminBundle:FileEdit')->findOneById($file_edit);

        $imagine        = $this->getImagine();

        if (null === $imagine || empty($file_edit))
            return null;

        $cache_path     = $this->getCachePath($file_edit->getParentEntity());
        $filepath       = $file_edit->getFile()->getPath() . '/' . $file_edit->getFile()->getFilename();
        $new_filepath   = $file_edit->getPath() . '/' . $file_edit->getFilename();
        $image          = $imagine->open($filepath);
        $edit_data      = $file_edit->getEditData();

        // rotate if defined
        if (isset($edit_data['rotate']) && (int)$edit_data['rotate'] !== 0) {
            $rotation       = (int)$edit_data['rotate'];
            $image->rotate($rotation);
        }

        // flip horizontally if defined
        if (isset($edit_data['scaleX']) && (int)$edit_data['scaleX'] !== 1) {
            $image->flipHorizontally();
        }

        // flip vertically if defined
        if (isset($edit_data['scaleY']) && (int)$edit_data['scaleY'] !== 1) {
            $image->flipVertically();
        }

        if (isset($edit_data['x']) && isset($edit_data['y']) && isset($edit_data['width']) && isset($edit_data['height'])) {
            // crop infos
            $crop_point     = new Point($edit_data['x'], $edit_data['y']);
            $crop_box       = new Box($edit_data['width'], $edit_data['height']);

            // crop image
            $image->crop($crop_point, $crop_box);
        }
        
        // save image
        $image->save($new_filepath);

        return '/' . $new_filepath;
    }

    public function getFilePath($file, $parent_entity, $property)
    {
        if (!is_object($file))
            $file       = $this->em->getRepository('DufAdminBundle:File')->findOneById($file);

        if (!empty($file)) {
            // check if file is image
            if ('images' === $file->getFiletype()) {
                // check if file is overriden in FileEdit
                $file_edit  = $this->em->getRepository('DufAdminBundle:FileEdit')->findOneBy(
                        array(
                            'file'                  => $file,
                            'parent_entity_id'      => $parent_entity->getId(),
                            'parent_entity'         => get_class($parent_entity),
                            'property'              => $property,
                        )
                    );

                if (!empty($file_edit)) {
                    // check if overriden file exists
                    $file_edit_path         = $file_edit->getPath() . '/' . $file_edit->getFilename();

                    // create edited file if it doesn't exist
                    if (!file_exists($file_edit_path))
                        $this->createFileEdit($file_edit);

                    return $file_edit_path;
                }
            }

            return $file->getPath() . '/' . $file->getFilename();
        }

        return null;
    }

    /**
     * Check if file's extension is allowed (defined in "allowed_upload_extensions" option in config.yml)
     *
     * @param string $filetype type of file (image, video, document)
     * @param string $extension name of the extension to validate
     * @return bool
    */
    private function isAllowedExtension($filetype, $extension)
    {
        $allowed_extensions = $this->getAllowedExtensions($filetype);

        foreach ($allowed_extensions as $allowed_extension) {
            if (strtolower($extension) == strtolower($allowed_extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get array of allowed extensions (defined in "allowed_upload_extensions" option in config.yml)
     *
     * @param string $filetype type of file (image, video, document)
     * @return array
    */
    private function getAllowedExtensions($filetype)
    {
        $allowed_extensions         = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('allowed_upload_extensions');

        foreach ($allowed_extensions as $allowed_filetype => $extensions) {
            if ($allowed_filetype === $filetype) {
                return $extensions;
            }
        }
    }

    private function getImagine()
    {
        if (extension_loaded('imagick')) {
            return new Imagick();
        }
        elseif (extension_loaded('gd') || extension_loaded('gd2')) {
            return new GdImagine();
        }

        return null;
    }

    private function getNewFilename($file, $parent_entity, $parent_entity_id, $append_dir = true)
    {
        $entity_class_hash      = substr(md5($parent_entity), 0, 10);
        $old_filename           = $file->getFilename();
        $new_filename           = $entity_class_hash . '_' . $parent_entity_id . '_' . $old_filename;
        $cache_path             = $this->getCachePath($parent_entity);

        // create folder if it doesn't exist
        if (!is_dir($cache_path)) {
            mkdir($cache_path);

            // set permissions
            $this->setCacheDirPermissions($cache_path);
        }

        if (!$append_dir)
            return $new_filename;

        return $cache_path . '/' . $new_filename;
    }

    private function getCachePath($parent_entity)
    {
        return 'uploads/images/cache';
    }

    private function setCacheDirPermissions($cache_path)
    {
        $cache_path_elements = explode('/', $cache_path);

        foreach ($cache_path_elements as $dir) {
            chmod($dir, '777');
        }
    }

    private function removeFileEditEntities($file, $parent_entity, $parent_entity_id)
    {
        $file_edits     = $this->em->getRepository('DufAdminBundle:FileEdit')->findBy(
                array(
                    'file'              => $file,
                    'parent_entity'     => $parent_entity,
                    'parent_entity_id'  => $parent_entity_id,
                )
            );

        foreach ($file_edits as $file_edit) {
            $this->em->remove($file_edit);
        }

        $this->em->flush();
    }
}