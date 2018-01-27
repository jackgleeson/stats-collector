<?php

namespace Statistics\Collector\Traits;

use Statistics\Collector\Filter\LessThan;

trait CollectorFilters
{

    /**
     * @param mixed $value
     *
     * @return array
     */
    public function lessThan($value)
    {
        $filter = new LessThan(static::getInstance(), $value);
        return $filter->filter();
    }
}