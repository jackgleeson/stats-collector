<?php


namespace Statistics\Collector\Helper;


class TypeHelper
{

    /**
     * Check to see if a value type is either float or integer
     *
     * @param $value
     *
     * @return bool
     */
    public function isIntOrFloat($value)
    {
        return (is_int($value) || is_float($value));
    }

    /**
     * Recursive check to see if a value is either:
     * - a single float or integer
     * - or, an array of floats or integers
     *
     * PHP returns "double" for gettype() calls on floats
     * http://php.net/manual/en/function.gettype.php#refsect1-function.gettype-returnvalues
     *
     * @param $value
     *
     * @return bool
     */
    public function isIntOrFloatRecursive($value)
    {
        switch (gettype($value)) {
            case "integer":
            case "double":
                return true;
            case "array":
                foreach ($value as $v) {
                    if ($this->isIntOrFloatRecursive($v) === false) {
                        return false;
                    }
                }
                return true;
            default:
                return false;
        }
    }
}