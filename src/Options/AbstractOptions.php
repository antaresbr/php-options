<?php

namespace Antares\Support\Options;

use Antares\Support\Arr;
use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

abstract class AbstractOptions implements ArrayAccess, Countable, IteratorAggregate, Traversable, JsonSerializable
{
    /**
     * The options prototypes
     *
     * @var array
     */
    protected $prototypes;

    /**
     * The data options array
     *
     * @var array
     */
    protected $data;

    //--[ implements : start ]--

    //-- implements : ArrayAccess

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        //return Arr::get($this->data, $key);
        return $this->get($key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->forget($key);
    }

    //-- implements : Countable

    /**
     * Get items count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    //-- IteratorAggregate, Traversable

    /**
     * Get itarator
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    //-- JsonSerializable

    /**
     * Get items data itself for serialization
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    //--[ implements : end ]--

    /**
     * Set protected property data
     *
     * @return void
     */
    public function reset(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get protected property data
     *
     * @param boolean $includeAbsentProperties
     * @return array
     */
    public function all(bool $includeAbsentProperties = false)
    {
        if (!$includeAbsentProperties) {
            return $this->data;
        }

        $data = [];
        foreach (array_keys($this->prototypes) as $key) {
            $data[$key] = $this->get($key);
        }
        return $data;
    }

    /**
     * Get array data
     *
     * @param boolean $includeAbsentProperties
     * @return array
     */
    public function toArray(bool $includeAbsentProperties = false)
    {
        return $this->all();
    }

    /**
     * Get md5 hash from property $data
     *
     * @return string
     */
    public function hash()
    {
        return empty($this->data) ? '' : md5(serialize($this->data));
    }

    /**
     * Check if this infos has en empty data property
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * Check if this infos has some key
     *
     * @param mixed $key The key to serach
     * @return boolean
     */
    public function has($key)
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Set key value
     *
     * @param mixed $key The key to set value
     * @param mixed $value The The value
     * @return void
     */
    public function set($key, $value)
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Forget key
     *
     * @param mixed $key The key to forget
     * @param mixed $value The The value
     * @return void
     */
    public function forget($key)
    {
        Arr::forget($this->data, $key);
    }

    /**
     * Get key value
     *
     * @param string $key The key to serach value
     * @param array $prototype The prototype used to get the value
     * @return mixed
     */
    public function get($key, $prototype = [])
    {
        if (empty($key)) {
            throw OptionsException::forNoKeySupplied();
        }

        if (!empty($this->prototypes) and !Arr::has($this->prototypes, $key)) {
            throw OptionsException::forInvalidOption($key, implode(' | ', array_keys($this->prototypes)));
        }

        if (empty($prototype)) {
            $prototype = Arr::get($this->prototypes, $key, []);
        }

        $default = Arr::get($prototype, 'default');

        return Arr::get($this->data, $key, $default);
    }

    /**
     * Check if an inaccessible property is set
     *
     * @param string $key
     * @return boolean
     */
    public function __isset(string $key)
    {
        return ($this->has($key) or Arr::has($this->prototypes, $key));
    }

    /**
     * Get an inaccessible property
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Set an inaccessible property
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Validate the supplied value against valid values
     *
     * @param mixed $value The value to be validated
     * @param string|array $validValues The possible valid values list
     * @return mixed
     */
    public function isValidValue($value, $validValues)
    {
        $validValues = Arr::arrayed($validValues);

        return (array_search($value, $validValues) !== false);
    }

    /**
     * Protected prototypes property accessor
     *
     * @return array
     */
    public function getPrototypes()
    {
        return $this->prototypes;
    }

    /**
     * Get option prototype
     *
     * @return array
     */
    public function getPrototype($key)
    {
        if (!array_key_exists($key, $this->prototypes)) {
            throw OptionsException::forOptionPrototypeNotFound($key);
        }

        return $this->prototypes[$key];
    }

    /**
     * Set options prototypes
     *
     * @param array $prototypes
     * @return static
     */
    abstract public function setPrototypes(array $prototypes);
}
