<?php

namespace Antares\Support;

use Antares\Support\Options\OptionsException;
use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class Options implements ArrayAccess, Countable, IteratorAggregate, Traversable, JsonSerializable
{
    /**
     * Valid types
     */
    public const VALID_TYPES = [
        'array',
        'boolean',
        'double',
        'float',
        'integer',
        'mixed',
        'on/off',
        'object',
        'string',
    ];

    public const PROTOTYPE = [
        'required' => ['types' => 'boolean', 'default' => false],
        'nullable' => ['types' => 'boolean', 'default' => true],
        'default' => ['types' => 'mixed', 'default' => null],
        'values' => ['types' => 'mixed', 'default' => null],
        'types' => ['types' => 'string', 'default' => 'mixed'],
        'throwException' => ['types' => 'boolean', 'default' => true],
    ];

    /**
     * The array with the items
     *
     * @var array
     */
    protected $data;

    /**
     * The options prototypes
     *
     * @var array
     */
    protected $prototypes;

    //-- implements : ArrayAccess

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return Arr::get($this->data, $key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        Arr::forget($this->data, $key);
    }

    //-- implements : Countable

    /**
     * Get items count
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
    }

    //--[ implements : start ]--

    //-- IteratorAggregate, Traversable

    /**
     * Get itarator
     *
     * @return ArrayItarator
     */
    public function getIterator()
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
     * Class constructor
     *
     * @param array $data Data to be used in this object
     * @param array $prototypes The protorype applied to this object
     */
    public function __construct(array $data = [], array $prototypes = [])
    {
        $this->setPrototypes($prototypes);
        $this->reset($data);
    }

    /**
     * Set protected property data
     *
     * @return void
     */
    public function reset(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get protected property data
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get array data
     *
     * @return array
     */
    public function toArray()
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
     * @param array $prototype The prototype used to get the value for the
     * @return mixed
     */
    public function get($key, $prototype = [])
    {
        if (empty($key)) {
            throw OptionsException::forNoKeySupplied();
        }

        if (!empty($this->prototypes) and !Arr::has($this->prototypes, $key)) {
            throw OptionsException::forInvalidOption($key);
        }

        if (empty($prototype)) {
            $prototype = Arr::get($this->prototypes, $key, []);
        }

        $required = Arr::get($prototype, 'required', static::PROTOTYPE['required']['default']);
        $nullable = Arr::get($prototype, 'nullable', static::PROTOTYPE['nullable']['default']);
        $default = Arr::get($prototype, 'default', static::PROTOTYPE['default']['default']);
        $values = Arr::get($prototype, 'values', static::PROTOTYPE['values']['default']);
        $types = Arr::get($prototype, 'types', static::PROTOTYPE['types']['default']);
        $throwException = Arr::get($prototype, 'throwException', static::PROTOTYPE['throwException']['default']);

        if ($required and !$this->has($key) and $throwException) {
            throw OptionsException::forAbsentRequiredOption($key);
        }

        $value = Arr::get($this->data, $key, $default);

        if (is_null($value)) {
            if (!$nullable and $throwException) {
                throw OptionsException::forNullOption($key);
            }
        } else {
            $gotType = gettype($value);

            $isValidType = $this->isValidType($value, $gotType, $types);

            if (!$isValidType and $throwException) {
                throw OptionsException::forInvalidType($key, $types, $gotType, !Str::icIn(gettype($value), 'object', 'array') ? $value : null);
            }

            if (!empty($values)) {
                if (!$this->isValidValue($value, $values) and $throwException) {
                    throw OptionsException::forInvalidValue($key, $values, $value);
                }
            }
        }

        return $value;
    }

    /**
     * Validate a valued type
     *
     * @param string $value The value with type to be validated
     * @param string $type The type to be validated
     * @param string|array $validTypes The acceptable types
     * @return bool
     */
    protected function isValidType(&$value, &$type, $validTypes)
    {
        $validTypes = Arr::arrayed($validTypes);

        $isValid = false;

        foreach ($validTypes as $validType) {
            if ($type == 'string') {
                if ($validType == 'boolean' and Str::icIn($value, 'true', 'false')) {
                    $value = (boolean) $value;
                    $type = $validType;
                }
                if ($validType == 'on/off' and Str::icIn($value, 'on', 'off')) {
                    $type = $validType;
                }
            }

            if ($type == 'object' and !Str::icIn($validType, ...static::VALID_TYPES)) {
                if ($value instanceof $validType) {
                    $type = $validType;
                }
            }

            if (Str::icIn($validType, 'mixed', $type)) {
                $isValid = true;
                break;
            }
        }

        return $isValid;
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
     * Set options prototypes
     *
     * @param array $prototypes
     * @return void
     */
    public function setPrototypes(array $prototypes)
    {
        if (!empty($prototypes)) {
            $validator = new static();

            foreach ($prototypes as $option => $prototype) {
                if (!Arr::accessible($prototype)) {
                    throw OptionsException::forInvalidPrototype($option);
                }

                $validator->reset($prototype);

                foreach (array_keys($validator->data) as $key) {
                    if (!Arr::has(static::PROTOTYPE, $key)) {
                        throw OptionsException::forInvalidPrototypeKey($option, $key);
                    }
                    $validator->get($key, static::PROTOTYPE[$key]);
                }
            }
        }

        $this->prototypes = $prototypes;
    }

    /**
     * Make a brand new options object based on supplied options array
     *
     * @param array $data Data to be used in this object
     * @param array $prototypes The protorypes applied to this object
     * @return static
     */
    public static function make(array $data = [], array $prototypes = [])
    {
        return new static($data, $prototypes);
    }
}
