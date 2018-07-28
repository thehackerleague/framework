<?php

namespace Mods\View\Layout;

use Layout\Core\Contracts\ConfigResolver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class Config implements ConfigResolver
{
    protected $_config;

    public function __construct(ConfigRepository $config)
    {
        $this->_config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {        
        if ($key == 'handle_layout_section') {
            return $this->getCurrentLayoutSection();
        }
        return $this->_config->get('layout.'.$key, $default);
    }
    
    public function getCurrentLayoutSection()
    {
        return app()->area();
    }

    /**
     * Magically handle calls to certain methods on the config factory.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \ErrorException
     *
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->_config, $method], $parameters);
    }
}
