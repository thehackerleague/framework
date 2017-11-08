<?php 

namespace Mods\Foundation\Aspect;

use Illuminate\Support\Str;

class AdviceManager
{
	const BEFORE = 'before';

    const AROUND = 'around';

    const AFTER = 'after';

    /**
     * The registered advice for particular join point.
     *
     * @var array
     */
    protected $advices  = [];


    /**
     * The added processed advices.
     *
     * @var array
     */
    protected $processed  = [];

    /**
     * Wrapper for before advice register.
     *
     * @param  string $id
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    public function before($id, $target, $macro, $sortOrder = 10)
    {
        $this->register($id, static::BEFORE, $target, $macro, $sortOrder);
    }

    /**
     * Wrapper for around advice register.
     *
     * @param  string $id
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    public function around($id, $target, $macro, $sortOrder = 10)
    {
        $this->register($id, static::AROUND, $target, $macro, $sortOrder);
    }


    /**
     * Wrapper for after advice register.
     *
     * @param  string $id
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    public function after($id, $target, $macro, $sortOrder = 10)
    {
        $this->register($id, static::AFTER, $target, $macro, $sortOrder);
    }

    /**
     * Register a advice for particular join point.
     *
     * @param  string $id
     * @param  string $joinPoint
     * @param  string $target
     * @param  string|callable  $macro
     * @param  int $sortOrder
     *
     * @return void
     */
    public function register($id, $joinPoint, $target, $macro, $sortOrder = 10)
    {
        list($target, $method) = Str::parseCallback($target);

        if (!$method) {
            return;
        }

        $this->advices[$target][$method][$joinPoint][$id] = [
            'weaver' => $macro,
            'order' => $sortOrder,
        ];
    }

    /**
     * Get the registed advice for particular join point for a target.
     *
     * @param  string $joinPoint
     * @param  string $target
     *
     * @return array
     */
    public function get($target)
    {
        list($target, $method) = Str::parseTarget($target);

        if (!$method) {
            return isset($this->advices[$target]) ?
                $this->advices[$target]
                : [];
        }

        return isset($this->advices[$target][$method]) ?
                $this->advices[$target][$method]
                : [];
    }

    /**
     * Get all the registed advice.
     *
     * @return array
     */
    public function all()
    {
        return $this->advices;
    }

    /**
     * Register a processd advice.
     *
     * @param  string $target
     * @return void
     */
    public function processed($target)
    {
       $this->processed[$target] = true;
    }


    /**
     * Check if a target is processd.
     *
     * @param  string $target
     * @return bool
     */
    public function isProcessed($target)
    {
       return isset($this->processed[$target]);
    }
}