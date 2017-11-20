<?php

namespace Statistics\Collector\Traits;


trait MathAggregate
{

    /**
     * Get the average of a collection of values
     *
     * @param array $values
     *
     * @return float|int
     */
    protected function mathAverage($values = [])
    {
        return (count($values) > 0) ? array_sum($values) / count($values) : 0;
    }

    /**
     * Get the sum of a collection of values
     *
     * @param array $values
     *
     * @return float|int
     */
    protected function mathSum($values = [])
    {
        return array_sum($values);
    }
}