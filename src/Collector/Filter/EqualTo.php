<?php

namespace Statistics\Collector\Filter;

class EqualTo extends AbstractFilter
{

    /**
     * @param array $params
     *
     * @return bool
     */
    protected function condition(...$params)
    {
        return ($value == $this->filterValue);
    }

}