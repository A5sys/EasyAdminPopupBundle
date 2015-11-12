<?php

namespace A5sys\EasyAdminPopupBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 *
 * @author Thomas BEAUJEAN
 */
class Configuration implements ConfigurationInterface
{
    /**
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('easy_admin_popup');

        $rootNode
        ->children()
            ->scalarNode('layout')->isRequired()
            ->end()
            ->booleanNode('customized_flash')
                ->defaultFalse()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
