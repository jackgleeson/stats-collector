<?php

namespace Statistics\Collector\Helper;


class MathHelper
{

    /**
     * @var \Statistics\Collector\Helper\TypeHelper
     */
    protected $typeHelper;

    /**
     * MathHelper constructor.
     */
    public function __construct()
    {
        $this->typeHelper = new TypeHelper();
    }


    /**
     * Check if value is a number or a collection of numbers available to
     * summed.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isSummable($value)
    {
        return $this->typeHelper->isIntOrFloatRecursive($value);
    }

    /**
     * Check if value is a number or a collection of numbers available to
     * averaged.
     *
     * @param $value
     *
     * @return bool
     */
    public function isAverageable($value)
    {
        return $this->typeHelper->isIntOrFloatRecursive($value);
    }


    /**
     * Get the average of an array of values
     *
     * @param array $values
     *
     * @return float|int
     */
    public function average($values = [])
    {
        return (count($values) > 0) ? array_sum($values) / count($values) : 0;
    }

    /**
     * Get the sum of an array of values
     *
     * @param array $values
     *
     * @return float|int
     */
    public function sum($values = [])
    {
        return array_sum($values);
    }

    /**
     * Get the count of items in an array
     *
     * @param $values
     *
     * @return int
     */
    public function count($values)
    {
        return count($values);
    }
}