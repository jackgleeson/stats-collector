<?php
require __DIR__ . '/vendor/autoload.php';

$StatsCollector = Statistics\Collector::getInstance();

// dummy data
$class = new StdClass;
$class->name = "Mr Class";
$clicks = 993924;
$visits = 657;

// adding stats with different value types
$StatsCollector->setNamespace("test.namespace")
    ->addStat("clicks", $clicks)
    ->addStat("visits", $visits);

// more advanced properties for backend specific handling?
//    ->addStat("object", $class)
//    ->addStat("json", json_encode($class))
//    ->addStat("custom", ["_type" => "summary", "value" => 6]);

// cats removing stats
$StatsCollector->setNamespace("another.test.namespace.created.by.cats")
    ->addStat("dogs_likeability", 100)
    ->removeStat("dogs_likeability")
    ->addStat("cats_likeability", 100);

//increment a stat
$StatsCollector->setNamespace("test.namespace")
    ->incrementStat("clicks");

//decrement a stat
$StatsCollector->setNamespace("test.namespace")
    ->decrementStat("visits");

// stat averages
$StatsCollector->setNamespace("test.averages")
    ->addStat("age", 23)
    ->addStat("age", 12)
    ->addStat("age", 74)
    ->addStat("age", 49)
    ->addStat("age", 9)
    ->addStat("height", 123);


var_dump($StatsCollector->getAllStats());
var_dump($StatsCollector->setNamespace("test.averages")->getStatAverage("age"));
var_dump($StatsCollector->setNamespace("test.averages")->getStatAverage("height"));


?>