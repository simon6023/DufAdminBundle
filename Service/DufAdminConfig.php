<?php
namespace Duf\Bundle\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DufAdminConfig
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Retrieves DufAdmin configuration from config.yml
     *
     * @param string $nodes optional name of the configuration node, including parent nodes
     * @return mixed content of configuration node
     *
    */
    public function getDufAdminConfig($nodes = null)
    {
        $node_string = '';
        if (null !== $nodes) {
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    $node_string .= '.' . $node;
                }
            }
            else {
                $node_string = '.' . $nodes;
            }
        }

        return $this->container->getParameter('duf_admin' . $node_string);
    }
}