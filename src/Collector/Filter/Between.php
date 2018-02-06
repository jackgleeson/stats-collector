<?php

namespace Statistics\Collector\Filter;

class Between extends AbstractFilter
{
    /**
     * @param $value
     *
     * @return bool
     */
    protected function condition($value)
    {
        if(count($this->filterParams)===2) {
            return  ($value >= $this->filterParams[0] && $value <= $this->filterParams[1]);
        } else {
            throw new StatisticsCollectorFilterException("GreaterThan filter only requires one argument which should be the value for stats values to be less than");
        }
        return ($value >= $this->filterValue[0] && $value <= $this->filterValue[1]);
    }

}