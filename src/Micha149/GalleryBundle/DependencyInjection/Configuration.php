<?php


namespace Micha149\GalleryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $debug;

    public function  __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('gallery');

        $rootNode
            ->children()
                ->scalarNode('bucket_name')->isRequired()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
