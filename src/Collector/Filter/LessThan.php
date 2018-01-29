<?php

namespace Statistics\Collector\Filter;

use Statistics\Exception\StatisticsCollectorFilterException;

class LessThan extends AbstractFilter
{

    /**
     * @param array $params
     *
     * @return bool
     * @throws \Statistics\Exception\StatisticsCollectorFilterException
     */
    protected function condition()
    {
        if(count($params)===1) {
            return ($params[0] < $this->filterValue);
        } else {
            throw new StatisticsCollectorFilterException("LessThan filter only requires one argument which should be the value for stats values to be less than");
        }
    }

}