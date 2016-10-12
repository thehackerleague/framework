<?php

namespace Thl\View;

use Layout\Core\Layout;
use Layout\Core\Factory as LayoutFactory;

class Factory
{
    /**
     * @var \Layout\Core\Factory $factory
     */
    protected $factory;

    /**
     * @var \Layout\Core\Layout $layout
     */
    protected $layout;

    public function __construct(LayoutFactory $factory, Layout $layout)
    {
        $this->factory = $factory;
        $this->layout = $layout;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function render(
        $handles = null,
        $generateBlocks = true,
        $generateXml = true,
        $disableRouteHandle = false
    ) {
        $factory = $this->factory->setLayout($this->layout);
        $html = $factory->render($handles, $generateBlocks, $generateXml, $disableRouteHandle);
        return view('root', ['html' => $html]);
    }
}
