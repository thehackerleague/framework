<?php

namespace Mods\View;

use Layout\Core\Block\AbstractBlock as BaseBlock;

class Block extends BaseBlock
{
    /**
     * Get relevant path to template.
     *
     * @return string
     */
    public function getTemplate()
    {
        $section = $this->config->get('handle_layout_section');

        $template = explode("::", $this->template);

        if (count($template) == 2) {
            $template = "{$template[0]}_$section::{$template[1]}";
        } else {
            $template = "$fileLocation::{$template[0]}";
        }
        
        return $template;
    }

    protected function getView($fileName, $viewVars)
    {
        return app('view')->make($fileName, $viewVars)->render();
    }
}
