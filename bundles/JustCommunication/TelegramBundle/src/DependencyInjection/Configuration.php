<?php

namespace JustCommunication\TelegramBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        // TODO: Implement getConfigTreeBuilder() method.
        $treeBuilder = new TreeBuilder('telegram');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('my_param')
                    ->isRequired()
                    ->info('Param pam pam')
                    ->end()
                ->arrayNode('arr')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('per_day')
                            ->defaultValue(10)
                            ->validate()
                                ->ifTrue(function ($v) { return $v <= 0; })
                                ->thenInvalid('Number must be positive')
                            ->end()
                        ->end()
                        ->integerNode('per_month')
                            ->defaultValue(100)
                            ->validate()
                                ->ifTrue(function ($v) { return $v <= 0; })
                                ->thenInvalid('Number must be positive')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            //->booleanNode('enable_soft_delete')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}