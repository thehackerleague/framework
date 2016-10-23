<?php

namespace Mods\View\Layout;

use Layout\Core\Contracts\ConfigResolver;
use Layout\Core\Exceptions\InvalidRouterHandleException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class Config implements ConfigResolver
{
    protected $_config;

    public function __construct(ConfigRepository $config)
    {
        $this->_config = $config;
    }

    public function get($key, $default = null)
    {
        if ($key == 'current_route_handle') {
            return $this->getCurrentRoute();
        }
        if ($key == 'handle_layout_section') {
            return $this->getCurrentLayoutSection();
        }
        return $this->_config->get('layout.'.$key, $default);
    }

    public function getCurrentRoute()
    {
        $routeName = \Route::currentRouteName();
        $routerHandler = str_replace('.', '_', strtolower($routeName));
        if (empty($routerHandler) || is_null($routerHandler)) {
            if ($this->_config->get('layout.strict', false)) {
                throw new InvalidRouterHandleException('Invalid Router Handle supplied');
            }
        }
        return $routerHandler;
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
