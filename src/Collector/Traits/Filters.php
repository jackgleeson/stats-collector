<?php

namespace Statistics\Collector\Traits;

trait Filters
{

    public function lessThan($value)
    {
        return new LessThanFilter($this, $value);
    }
}