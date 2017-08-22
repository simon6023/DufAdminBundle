<?php
namespace Duf\AdminBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Duf\AdminBundle\DependencyInjection\Configuration;

class DufAdminExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration 		= new Configuration();
        $processedConfig 	= $this->processConfiguration($configuration, $configs);

        $container->setParameter('duf_admin.sidebar_sections', $processedConfig['sidebar_sections']);
        $container->setParameter('duf_admin.entities', $processedConfig['entities']);
        $container->setParameter('duf_admin.ecommerce_entities', $processedConfig['ecommerce_entities']);
        $container->setParameter('duf_admin.site_name', $processedConfig['site_name']);
        $container->setParameter('duf_admin.aggregator_enabled', $processedConfig['aggregator_enabled']);
        $container->setParameter('duf_admin.cron_enabled', $processedConfig['cron_enabled']);
        $container->setParameter('duf_admin.cron_delay', $processedConfig['cron_delay']);
        $container->setParameter('duf_admin.ecommerce_enabled', $processedConfig['ecommerce_enabled']);
        $container->setParameter('duf_admin.ecommerce_cart_entity', $processedConfig['ecommerce_cart_entity']);
        $container->setParameter('duf_admin.ecommerce_cart_redirect_route', $processedConfig['ecommerce_cart_redirect_route']);
        $container->setParameter('duf_admin.user_entity', $processedConfig['user_entity']);
        $container->setParameter('duf_admin.user_embed_entities', $processedConfig['user_embed_entities']);
        $container->setParameter('duf_admin.user_role_entity', $processedConfig['user_role_entity']);
        $container->setParameter('duf_admin.language_entity', $processedConfig['language_entity']);
        $container->setParameter('duf_admin.upload_dir', $processedConfig['upload_dir']);
        $container->setParameter('duf_admin.file_system_items_per_page', $processedConfig['file_system_items_per_page']);
        $container->setParameter('duf_admin.allowed_upload_extensions', $processedConfig['allowed_upload_extensions']);
        $container->setParameter('duf_admin.redmine', $processedConfig['redmine']);
        $container->setParameter('duf_admin.admin_assets', $processedConfig['admin_assets']);
    }
}