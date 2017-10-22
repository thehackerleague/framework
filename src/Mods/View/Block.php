<?php

namespace Mods\View;

use  Illuminate\View\ViewFinderInterface;
use Layout\Core\Block\AbstractBlock as BaseBlock;

abstract class Block extends BaseBlock
{
    /**
     * Get relevant path to template.
     *
     * @return string
     */
    public function getTemplate()
    {
        if(is_null($this->template)) {
            return null;
        }

        $section = $this->config->get('handle_layout_section');

        $template = explode(ViewFinderInterface::HINT_PATH_DELIMITER, $this->template);

        if (count($template) == 2) {
            $template = "{$template[0]}_$section::{$template[1]}";
        } else {
            $template = "$section::{$template[0]}";
        }
        
        return $template;
    }

    protected function getView($fileName, $viewVars)
    {
        return app('view')->make($fileName, $viewVars)->render();
    }
}
