<?php

namespace Statistics\Collector\Filter;

class Between extends AbstractFilter
{
    /**
     * @param $value
     *
     * @return bool
     */
    protected function condition($lowerRangeValue, $upperRangeValue)
    {
        return ($value == $this->filterValue);
    }

}