<?php

namespace Mods\View;

use Layout\Core\PageFactory;

class Factory
{
    /**
     * @var \Layout\Core\Factory $factory
     */
    protected $factory;

    public function __construct(PageFactory $factory)
    {
        $this->factory = $factory;
    }
    
    public function render() {
        $html = $this->factory->render();
        return view('root', $html);
    }
}
