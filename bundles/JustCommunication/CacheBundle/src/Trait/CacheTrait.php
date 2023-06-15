<?php
namespace JustCommunication\CacheBundle\Trait;

use JustCommunication\CacheBundle\Service\CacheHelper;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait CacheTrait
{
    #[Required]
    public CacheHelper $cacheHelper;

    public function cached($cache_name, $callback, $force = false){
        if ($force) {
            $this->cacheHelper->getCache()->delete($cache_name);
        }
        return $this->cacheHelper->getCache()->get($cache_name, function (ItemInterface $item) use ($callback) {
            //echo '-get-from-callback-';
            return $callback();
        });
    }

    //public function cache_delete($cache_name){
    //    $this->cacheHelper
    //}

}