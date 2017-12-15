<?php

namespace Statistics\Collector;

interface iCollector
{

    public function addStat($name, $value, $options);

    public function removeStat($namespace);

    public function getStat($namespace, $withKeys, $default);

    public function getStats(array $namespaces, $withKeys, $default);

    public function getStatCount($namespace);

    public function getStatsCount(array $namespace);

    public function getStatAverage($namespace);

    public function getStatsAverage(array $namespace);

    public function getStatSum($namespace);

    public function getStatsSum(array $namespace);

    public function incrementStat($namespace, $increment);

    public function decrementStat($namespace, $decrement);

    public function getAllStats();

    public function setNamespace($namespace);

    public function getCurrentNamespace();

}