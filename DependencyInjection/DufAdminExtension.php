<?php
namespace Duf\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Duf\AdminBundle\DependencyInjection\Configuration;

class DufAdminExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration 		= new Configuration();
        $processedConfig 	= $this->processConfiguration($configuration, $configs);

        $container->setParameter('duf_admin.entities', $processedConfig['entities']);
        $container->setParameter('duf_admin.site_name', $processedConfig['site_name']);
        $container->setParameter('duf_admin.user_entity', $processedConfig['user_entity']);
        $container->setParameter('duf_admin.user_embed_entities', $processedConfig['user_embed_entities']);
        $container->setParameter('duf_admin.user_role_entity', $processedConfig['user_role_entity']);
        $container->setParameter('duf_admin.language_entity', $processedConfig['language_entity']);
        $container->setParameter('duf_admin.upload_dir', $processedConfig['upload_dir']);
        $container->setParameter('duf_admin.file_system_items_per_page', $processedConfig['file_system_items_per_page']);
        $container->setParameter('duf_admin.allowed_upload_extensions', $processedConfig['allowed_upload_extensions']);
        $container->setParameter('duf_admin.redmine', $processedConfig['redmine']);
    }
}