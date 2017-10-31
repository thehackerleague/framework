<?php

namespace Mods\Database\Eloquent;

use LogicException;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;

class Model extends IlluminateModel
{
	use Macroable {
        __call as macroCall;
        __callStatic as macroStaticCall;
    }
    use HasAttributes {
    	getRelationValue as attributesGetRelationValue;
    }


    /**
     * Get a relationship.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
    	// If the key exists as macro, we will check if
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        // else if its just a function we will return the result
    	if (static::hasMacro($key)) {
            return $this->getRelationshipOrMacro($key);
        }

        // We will let the HasAttributes trait handle the rest
        return $this->attributesGetRelationValue($key);
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipOrMacro($method)
    {
        $relation = $this->macroCall($method, []);

        if (! $relation instanceof Relation) {
            //throw new LogicException(get_class($this).'::'.$method.' must return a relationship instance.');
            return $relation;
        }

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

	/**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
    	if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }

        try {
            return $this->newQuery()->$method(...$parameters);
        } catch (BadMethodCallException $e) {
            throw new BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', get_class($this), $method)
            );
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {	
    	if (static::hasMacro($method)) {
            return $this->macroStaticCall($method, $parameters);
        }

        return (new static)->$method(...$parameters);
    }
}