<?php
namespace JustCommunication\TelegramBundle\DependencyInjection;

use JustCommunication\TelegramBundle\Controller\NewController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class TelegramExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        //dd($configs);
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');


        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
/*
        $container->setParameter(
            'justcommunication.telegram.pumpum',
            $config['my_param']
        );
*/
        $definition = $container->getDefinition(NewController::class);
        $definition->setArguments([
            '$my_param' => $config['my_param'],
        ]);
        $container->setParameter(
            'arr',
            $config['arr']
        );
        //*/
    }
}