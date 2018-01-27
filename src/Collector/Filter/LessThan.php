<?php

namespace Statistics\Collector\Filter;

class LessThan extends AbstractFilter
{
    /**
     * @param $value
     *
     * @return bool
     */
    protected function condition($value)
    {
        return $value < $this->filterValue;
    }

}