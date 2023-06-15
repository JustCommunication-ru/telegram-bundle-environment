<?php

namespace JustCommunication\CacheBundle\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheHelper
{
    public $cache;
    public $client;

    public function __construct(ParameterBagInterface $params)
    {
        $config_memcache = $params->get("memcache");

        //dd($config_memcache);
        /**
         * Кэш-клиенту глубоко всё равно есть коннект к мемкэшу или нет. порт, хост, можно передать любую шляпу
         * и будет ощущение что все работает
         */
        try {
            $this->client = MemcachedAdapter::createConnection(
                'memcached://' . ($config_memcache['host'] ?? '127.0.0.1') . ':' . ($config_memcache['port'] ?? '11211')
            );
            $this->cache = new MemcachedAdapter(
                $this->client,
                $namespace = $config_memcache['namespace'] ?? '',
                $defaultLifetime = $config_memcache['lifetime'] ?? 86400
            );
            $this->test_connection(rand(1111,9999));



        } catch (\Exception $e) {
            // А че делать? пока просто передадим в космос
            throw new \Exception($e->getMessage());
        }
        // если в test_connection случится какая-то шляпа (например нет коннекта), то тут мы это отловим
        if ($this->client->getLastErrorCode()>0){
            throw new \Exception($this->client->getLastErrorMessage());
        }

        //echo $this->test("111");
        //$this->cache->delete('my_cache_key');
        //echo $this->test("222");

        //$cache->delete('my_cache_key');
    }

    public function getCache(){
        return $this->cache;
    }

/*
    public function save(){
        $productsCount = $this->cache->getItem('stats.products_count');

// assign a value to the item and save it
        $productsCount->set(4711);
        $cache->save($productsCount);
    }
    */

    /**
     * Пример использования
     * @param int $x
     * @param false $force
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function  test_connection($x=5, $force=false){
        //$cache = new FilesystemAdapter();
        if ($force){
            $this->cache->delete('my_cache_key');
        }
        try {
            $value = $this->cache->get('my_cache_key', function (ItemInterface $item) use ($x) {
                $item->expiresAfter(3600);
                //$item->set($computedValue);
                return 'foobar_' . $x;
            });
        } catch (InvalidArgumentException $e) {
            echo '!!!!!!!!';
        }
        return $value; // 'foobar'
    }

}
