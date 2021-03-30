<?php

namespace App\Service;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use JsonSerializable;
use ReflectionClass;

/**
 * Class BaseEnum
 * @package App\Enums
 */

abstract class BaseEnum implements JsonSerializable
{
    private $value;

    /**
     * @return string[]
     */
    abstract public function display();

    /**
     * BaseEnum constructor.
     * @param $key
     */
    private final function __construct($key = null)
    {
        $this->setByKey($key);
    }

    /**
     * @param $key
     */
    private function setByKey($key)
    {
        if (empty($key)) {
            return;
        }

        $this->value = constant(static::class . '::' . $key);
    }

    /**
     * @param $value
     */
    protected function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param $name
     * @param $arguments
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        return new static($name);
    }

    /**
     * @param $value
     * @return static
     */
    public static function get($value)
    {
        return new static(
            array_search($value, self::all())
        );
    }

    /**
     * @param $text
     * @return static|null
     */
    public static function search($text)
    {
        $class = new static();

        if ($value = array_search($text, $class->display())) {
            $class->setValue($value);
            return $class;
        }

        return null;
    }

    /**
     * @return array
     */
    public static function all()
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * @return array
     */
    public static function keys()
    {
        return array_keys(self::all());
    }

    /**
     * @return array
     */
    public static function values()
    {
        return array_values(self::all());
    }

    /**
     * @return string[]
     */
    public static function list()
    {
        return (new static())->display();
    }

    /**
     * @return In
     */
    public static function rule()
    {
        return Rule::in(self::values());
    }

    /**
     * @param self|string $enum
     * @return bool
     */
    public function equal($enum)
    {
        if (is_string($enum)) {
            return $this->value === $enum;
        }

        return get_class($this) === get_class($enum) && $this->value() === $enum->value();
    }

    /**
     * @param self[]|string[] $enums
     * @return bool
     */
    public function include(array $enums)
    {
        foreach ($enums as $enum) {
            if ($this->equal($enum)) {
                return true;
            }
        }

        return false;
    }
}
