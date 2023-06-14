<?php
namespace JustCommunication\TelegramBundle\DependencyInjection;

use JustCommunication\TelegramBundle\Controller\NewController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class TelegramExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container):void
    {
        //dd($configs);
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');


        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        //$x = $container->getParameter('doctrine.orm.security.user.provider.class');
        //var_dump($container);

        // Превращаем конфиги из packages/telegram.yaml в параметры, которые можем вытащить через ParameterBagInterface
        if (isset($config['config'])) {
            $container->setParameter(
                'justcommunication.telegram.config',
                $config['config']
            );
        }
        if (isset($config['events'])) {
            $container->setParameter(
                'justcommunication.telegram.events',
                $config['events']
            );
        }



        /*
        $definition = $container->getDefinition(NewController::class);
        $definition->setArguments([
            '$my_param' => $config['my_param'],
        ]);
        //*/
    }
}