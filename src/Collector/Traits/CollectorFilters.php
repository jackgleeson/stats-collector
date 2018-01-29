<?php

namespace Statistics\Collector\Traits;

use Statistics\Collector\Filter\LessThan;
use Statistics\Collector\Filter\GreaterThan;
use Statistics\Collector\Filter\EqualTo;

trait CollectorFilters
{

    /**
     * @param mixed $value
     *
     * @return array
     */
    public function lessThan($value)
    {
        $filter = new LessThan($this->getAllStats(), $value);
        return $filter->filter();
    }

    public function greaterThan($value)
    {
        $filter = new GreaterThan($this->getAllStats(),$value);
        return $filter->filter();
    }

    public function equalTo($value)
    {
        $filter = new EqualTo($this->getAllStats(), $value);
        return $filter->filter();
    }

    public function between($lowerRangeValue, $upperRangeValue)
    {
        $filter = new Between($this->getAllStats(),$lowerRangeValue, $upperRangeValue);
        return $filter->filter();
    }
}