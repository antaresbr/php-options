<?php

namespace Antares\Support;

use Antares\Support\Options\AbstractOptions;
use Antares\Support\Options\OptionsException;

class Options extends AbstractOptions
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
        'required' => ['type' => 'boolean', 'default' => false],
        'nullable' => ['type' => 'boolean', 'default' => true],
        'default' => ['type' => 'mixed', 'default' => null],
        'values' => ['type' => 'array', 'default' => []],
        'type' => ['type' => 'string|array', 'default' => 'mixed'],
        'throwException' => ['type' => 'boolean', 'default' => true],
    ];

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
            throw OptionsException::forInvalidOption($key, implode(' | ', array_keys($this->prototypes)));
        }

        if (empty($prototype)) {
            $prototype = Arr::get($this->prototypes, $key, []);
        }

        $required = Arr::get($prototype, 'required', static::PROTOTYPE['required']['default']);
        $nullable = Arr::get($prototype, 'nullable', static::PROTOTYPE['nullable']['default']);
        $default = Arr::get($prototype, 'default', static::PROTOTYPE['default']['default']);
        $values = Arr::get($prototype, 'values', static::PROTOTYPE['values']['default']);
        $types = Arr::arrayed(Arr::get($prototype, 'type', static::PROTOTYPE['type']['default']));
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
                if ($gotType == 'object') {
                    $gotType = get_class($value);
                }
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
     * Validate data with current prototypes
     *
     * @return static
     */
    public function validate()
    {
        if (!$this->isEmpty() and !empty($this->prototypes)) {
            foreach (array_keys($this->data) as $key) {
                $this->get($key);
            }
        }

        return $this;
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
