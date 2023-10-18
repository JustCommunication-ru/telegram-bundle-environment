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

                        ->scalarNode('app_name')
                            ->info('project name, by default it is "%env(APP_NAME)%"')
                            ->defaultValue('%env(APP_NAME)%')
                        ->end()

                        ->scalarNode('production_webhook_app_url')
                            ->info('real URL of your project in www. Used in bin/console app:telegram --init')
                            ->defaultValue('')
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
                        ->booleanNode('webhook_logging_turn_on')
                            ->info('Save webhook processing file log in /var/cache/telegram.txt')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('user_entity_class')
                            ->info('fully qualified classname of User Entity')
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