<?php


namespace Statistics\Collector\Helper;


class ArrayHelper
{

    /**
     * Flatten a multi-dimensional array down to a single array
     *
     * @param array $array
     *
     * @return array
     */
    public function flatten($array = [])
    {
        $flattened = [];
        array_walk_recursive($array, function ($a) use (&$flattened) {
            $flattened[] = $a;
        });
        return $flattened;
    }
}