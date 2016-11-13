<?php

namespace  Mods\Theme\Console;

abstract class Console
{
    protected $console;
    
    public function setConsole($console)
    {
        $this->console = $console;
        return $this;
    }

    protected function info($msg)
    {
        if ($this->console) {
            $this->console->info($msg);
        }
    }

    protected function warn($msg)
    {
        if ($this->console) {
            $this->console->warn($msg);
        }
    }

    protected function error($msg)
    {
        if ($this->console) {
            $this->console->error($msg);
        }
    }

    protected function line($msg)
    {
        if ($this->console) {
            $this->console->line($msg);
        }
    }
}
