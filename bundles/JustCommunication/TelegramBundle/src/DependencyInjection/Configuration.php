<?php

namespace JustCommunication\TelegramBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder():TreeBuilder
    {
        // TODO: Implement getConfigTreeBuilder() method.
        $treeBuilder = new TreeBuilder('telegram');
/*
        $treeBuilder->getRootNode()

            

            ->children()
                ->booleanNode('auto_connect')
                ->defaultTrue()
                ->end()
                ->scalarNode('default_connection')
                ->defaultValue('default')
                ->end()

                ->arrayNode('config')
                    //->useAttributeAsKey('name')
                    //->arrayPrototype()
                    ->children()
                        ->scalarNode('table')
                        ->end()


                        //->scalarNode('admin_chat_id')
                            ->variableNode('admin_chat_id')
                        //->isRequired()
                        ->info('Telegram chat id of special super admin user')
                        ->end()

            ->scalarNode('message_prefix')
            ->info('Telegram chat id of special admin user')
            ->end()


                    ->end()
                ->end()

            ->end()
*/
/*
            ->arrayPrototype()
            ->children()
            ->scalarNode('table')->end()
            ->scalarNode('user')->end()
            ->scalarNode('password')->end()
            */
        ;



        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('some_param')
                ->end()
                ->arrayNode('config')
                    ->children()
                        ->integerNode('admin_chat_id')
                            ->isRequired()
        /*
                            ->validate()
                                ->ifTrue(function ($v) { return $v <= 0; })
                                ->thenInvalid('admin_chat_id must be valid telegram chat id number')
                            ->end()
        */
                            ->info('Telegram chat id of special super admin user')
                        ->end()

                        ->scalarNode('message_prefix')
                            ->info('Telegram chat id of special admin user')
                        ->end()

                        ->scalarNode('bot_name')
                            ->info('Name of bot which be shown in sms to user')
                        ->end()

                        ->scalarNode('url')
                            ->info('Official telegram API url')
                            ->isRequired()
                            //->defaultValue('https://api.telegram.org/bot') надо ли?
                        ->end()

                        ->scalarNode('proxy')
                            ->info('CURLOPT_PROXY value if needed')
                        ->end()

                        ->scalarNode('proxy_auth') // надо переименовать, что за auth блядь?
                            ->info('CURLOPT_PROXYUSERPWD value if needed')
                        ->end()

                        ->scalarNode('token')
                            ->info('Secret token to access bot by API')
                        ->end()

                        ->scalarNode('app_url')
                            ->info('local project url, by default it is "%env(APP_URL)%"')
                            ->defaultValue('%env(APP_URL)%')
                        ->end()

                        ->scalarNode('production_webhook_app_url')
                            ->info('real URL of your project in www. Used in bin/console app:telegram --init')
                            ->defaultValue('')
                        ->end()

                        ->scalarNode('webhook_url_path')
                            ->info('URL on project to process messages from API, by default it is "/telegram/webhook"')
                            ->defaultValue('/telegram/webhook')
                        ->end()

                        ->scalarNode('user_link_url_path')
                            ->info('URL for link project user to telegram user, by default it is  "/user/telegram/link/hashplace"')
                            ->defaultValue('/user/telegram/link/hashplace')
                        ->end()

                        ->booleanNode('wrong_event_exception')
                            ->info('Script will be fail if not existing event sent. This options something like strict mode')
                            ->defaultTrue()
                        ->end()

                        ->booleanNode('markdown_checker')
                            ->info('Force check and repair broken markdowns. It will be correct messages, but it will increase succesfull delivery')
                            ->defaultTrue()
                        ->end()

                        ->booleanNode('length_checker')
                            ->info('Cut to much long messages')
                            ->defaultTrue()
                        ->end()

                    ->end()
                ->end()
                ->arrayNode('events')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('note')->end()
                            ->ArrayNode('roles')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}