<?php

namespace Antares\Support\Options;

use Antares\Support\Str;
use Exception;

class OptionsException extends Exception
{
    /**
     * Create a new exception for not defined data
     *
     * @return static
     */
    public static function forNoDataSupplied()
    {
        return new static("No data supplied.\n");
    }

    /**
     * Create a new exception for no key supplied
     *
     * @return static
     */
    public static function forNoKeySupplied()
    {
        return new static("No key supplied.\n");
    }

    /**
     * Create a new exception for invalid prototype
     *
     * @param  string  $option
     * @return static
     */
    public static function forInvalidPrototype($option)
    {
        return new static("Invalid prototype for option '{$option}'.\n");
    }

    /**
     * Create a new exception for invalid prototype key for option
     *
     * @param  string  $option
     * @param  string  $key
     * @return static
     */
    public static function forInvalidPrototypeKey($option, $key)
    {
        return new static("Invalid prototype key '{$key}' for option '{$option}'.\n");
    }

    /**
     * Create a new exception for invalid option
     *
     * @param  string  $option
     * @return static
     */
    public static function forInvalidOption($option, $validOptions = null)
    {
        return new static(Str::join(
            ', ',
            "Invalid option '{$option}'",
            !empty($validOptions) ? "valid option(s) '{$validOptions}'" : ''
        ));
    }

    /**
     * Create a new exception for absent required option
     *
     * @param  string  $option
     * @return static
     */
    public static function forAbsentRequiredOption($option)
    {
        return new static("Absent required option: {$option}\n");
    }

    /**
     * Create a new exception for null option
     *
     * @param  string  $option
     * @return static
     */
    public static function forNullOption($option)
    {
        return new static("Option '{$option}' cannot be null.\n");
    }

    /**
     * Create a new exception for invalid option type
     *
     * @param  string  $option
     * @param  string  $desiredType
     * @param  string  $gotType
     * @param  string  $gotValue
     * @return static
     */
    public static function forInvalidType($option, $desiredType, $gotType, $gotValue = null)
    {
        if (!empty($desiredType) and is_array($desiredType)) {
            $desiredType = implode('|', $desiredType);
        }
        return new static(Str::join(
            ', ',
            "The type of option '{$option}' must be '{$desiredType}'",
            "but '{$gotType}' was gotten",
            !empty($gotValue) ? "with the value '{$gotValue}'" : ''
        ));
    }

    /**
     * Create a new exception for invalid option type
     *
     * @param  string  $option
     * @param  string  $validValues
     * @param  string  $gotValue
     * @return static
     */
    public static function forInvalidValue($option, $validValues, $gotValue)
    {
        if (is_array($validValues)) {
            $validValues = print_r($validValues, true);
        }
        return new static(Str::join(
            ', ',
            "The value '{$gotValue}' for option '{$option}' is invalid",
            "possible value(s) '{$validValues}'"
        ));
    }
}
