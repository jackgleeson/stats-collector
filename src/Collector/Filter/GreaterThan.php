<?php

namespace Statistics\Collector\Filter;

class GreaterThan extends AbstractFilter
{
    /**
     * @param $value
     *
     * @return bool
     */
    protected function condition(...$params)
    {
        if(count($params)===1) {
            return ($params[0] > $this->filterValue);
        } else {
            throw new StatisticsCollectorFilterException("GreaterThan filter only requires one argument which should be the value for stats values to be less than");
        }
    }

}