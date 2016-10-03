<?php

namespace Duf\AdminBundle\Twig;

class DufAdminExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('getFileSize', array($this, 'getFileSizeFilter')),
            new \Twig_SimpleFilter('getEntityClass', array($this, 'getEntityClassFilter')),
        );
    }

    public function getFileSizeFilter($filesize, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
        $bytes = max($filesize, 0); 
        $pow = floor(($filesize ? log($filesize) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $filesize /= pow(1024, $pow);
        // $filesize /= (1 << (10 * $pow)); 

        return round($filesize, $precision) . ' ' . $units[$pow]; 
    }

    public function getEntityClassFilter($entity)
    {
        return get_class($entity);
    }

    public function getName()
    {
        return 'dufAdminBundle_extension';
    }
}