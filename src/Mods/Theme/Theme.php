<?php

namespace Mods\Theme;

class Theme
{
    /**
     * Holds the theme info.
     *
     *  @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getArea()
    {
        return $this->data['area'];
    }

    public function getPath()
    {
        return $this->data['path'];
    }

    public function hasParent()
    {
        return isset($this->data['parent']) && !empty($this->data['parent']);
    }

    public function getParent()
    {
        if ($this->hasParent()) {
            return $this->data['parent'];
        }
        return null;
    }
}
