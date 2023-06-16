<?php

namespace JustCommunication\SmsAeroBundle\Service;

/**
 * Собственно хелпер только для того, чтобы было единое место инициализации и использование конфигов.
 */
use Predis\Client;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RedisHelper
{
    public $client;

    public function __construct(ParameterBagInterface $params)
    {
        $config_redis = $params->get("redis");
        $this->client = new Client($config_redis['parameters'], $config_redis['options']);

        /*
        // Пример параметров
        $parameters = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        $options = null;
        $this->client = new Client($parameters, $options);

        // Примеры использования

        //autowire (RedisHelper $redisHelper)
        $redis = $redisHelper->getClient();

        $value = $redis->get('counter');
                $redis->incr("counter");

        $key= 'sms_send_over';
        $list = $redis->hgetall($key);
        $redis->hincrby($key,$subkey, 1);
        $redis->hdel($key,$subkey);

        //*/
    }

    public function getClient(){
        return $this->client;
    }
}
