<?php
/**
 * Mamis.IT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is available through the world-wide-web at this URL:
 * http://www.mamis.com.au/licencing
 *
 * @category   Mamis
 * @copyright  Copyright (c) 2019 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.mamis.com.au/licencing
 */

// Contents of this class are based on string
// and array access implementations from the
// Laravel Framework
// @see https://github.com/laravel/framework

class Mamis_Shippit_Object implements ArrayAccess
{
    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = array();

    /**
     * The cache of snake-cased words.
     *
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * The cache of studly-cased words.
     *
     * @var array
     */
    protected static $studlyCache = [];

    /**
     * Attribute data handling wrapper
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $methodType = substr($method, 0, 3);
        $methodName = $methodType . 'Data';

        // If the method does not exist locally, throw an exception
        if (!method_exists($this, $methodName)) {
            throw new Exception(
                sprintf(
                    'Invalid method %s::%s (%s)',
                    get_class($this),
                    $method,
                    print_r($parameters, 1)
                )
            );
        }

        $attribute = substr($method, 3);

        // Retrieve the data key for this attribute
        $key = $this->snake($attribute);

        if ($methodType == 'set') {
            // Retrieve the data value for the attribute
            $value = (isset($parameters[0]) ? $parameters[0]: null);

            return $this->{$methodName}($key, $value);
        }
        else {
            return $this->{$methodName}($key);
        }
    }

    /**
     * Convert object attributes to JSON
     *
     * @param  array $attributes Array of required attributes
     * @return string
     */
    public function __toJson(array $attributes = array())
    {
        $arrayData = $this->toArray($attributes);

        return json_encode($arrayData);
    }

    /**
     * Public wrapper for __toJson
     *
     * @param array $attributes
     * @return string
     */
    public function toJson(array $attributes = array())
    {
        return $this->__toJson($attributes);
    }

    /**
     * Convert object attributes to array
     *
     * @param  array $arrAttributes array of required attributes
     * @return array
     */
    public function __toArray(array $attributes = array())
    {
        if (empty($attributes)) {
            return $this->_data;
        }

        $arrayData = array();

        foreach ($attributes as $attribute) {
            if (isset($this->_data[$attribute])) {
                $arrayData[$attribute] = $this->_data[$attribute];
            }
            else {
                $arrayData[$attribute] = null;
            }
        }

        return $arrayData;
    }

    /**
     * Public wrapper for __toArray
     *
     * @param array $attributes
     * @return array
     */
    public function toArray(array $attributes = array())
    {
        return $this->__toArray($attributes);
    }

    /**
     * Checks whether the object is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if (empty($this->_data)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves data from the object
     *
     * If $key is empty will return all the data as an array
     * Otherwise it will return value of the attribute specified by $key
     *
     * @param string $key
     * @return mixed
     */
    public function getData($key = null)
    {
        if (is_null($key)) {
            return $this->_data;
        }

        if (!isset($this->_data[$key])) {
            return;
        }

        return $this->_data[$key];
    }

    /**
     * Overwrite data in the object.
     *
     * @param string $key
     * @param mixed $value
     * @return Mamis_Shippit_Object
     */
    public function setData($key, $value = null)
    {
        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * Unsets the data from the object
     *
     * @param string $key
     * @return boolean
     */
    public function unsData($key)
    {
        unset($this->_data[$key]);

        return $this;
    }

    /**
     * Determines if the data is set in the object
     *
     * @param string $key
     * @return boolean
     */
    public function hasData($key)
    {
        return isset($this->_data[$key]);
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetset.php
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetexists.php
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetunset.php
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetget.php
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }
}
