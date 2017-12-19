<?php

namespace Statistics\Collector;

interface iCollectorShorthand
{

    public function add($name, $value, $options);

    public function del($namespace);

    public function get($namespace, $withKeys, $default);

    public function getWithKey($namespace, $default);

    public function clobber($name, $value, $options);

    public function inc($namespace, $increment);

    public function dec($namespace, $decrement);

    public function avg($namespace);

    public function sum($namespace);

    public function count($namespace);

    public function all();

    public function ns($namespace);


}