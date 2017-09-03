<?php

namespace  Mods\Theme\Asset;

abstract class Console
{
    protected $console;
    
    public function setConsole($console)
    {
        $this->console = $console;
        return $this;
    }

     /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    protected function info($string, $verbosity = null)
    {
        if ($this->console) {
            $this->console->info($string);
        }
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    protected function warn($string, $verbosity = null)
    {
        if ($this->console) {
            $this->console->warn($string);
        }
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    protected function error($string, $verbosity = null)
    {
        if ($this->console) {
            $this->console->error($string);
        }
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string  $style
     * @param  null|int|string  $verbosity
     * @return void
     */
    protected function line($string, $style = null, $verbosity = null)
    {
        if ($this->console) {
            $this->console->line($string);
        }
    }

    /**
     * Format input to textual table.
     *
     * @param  array   $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @param  string  $style
     * @return void
     */
    protected function table(array $headers, $rows, $style = 'default')
    {
        if ($this->console) {
            $this->console->table($headers, $rows, $style);
        }
    }
}
