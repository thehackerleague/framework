<?php

namespace Mods\View\Layout;

use ErrorException;
use Layout\Core\Contracts\Cacheable;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Factory as CacheContract;

class Cache implements Cacheable
{
    protected $_cache;

    public function __construct(CacheContract $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return $this->_cache->get($key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function put($key, $data, $time, $tags = [])
    {
        if (!\Cache::getStore() instanceof TaggableStore) {
            $this->cache->put($key, $data, $time);
        } else {
            $this->cache->tags($tags)->put($key, $data, $time);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function forever($key, $data, $tags = [])
    {
        if (!\Cache::getStore() instanceof TaggableStore) {
            return $this->_cache->forever($key, $data);
        } else {
            return $this->_cache->tags($tags)->forever($key, $data);
        }
    }

    /**
     * Magically handle calls to certain methods on the cache factory.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \ErrorException
     *
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->_cache, $method], $parameters);
    }
}
