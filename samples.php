<?php
require __DIR__ . '/vendor/autoload.php';

$Stats = Statistics\Collector::getInstance();

// dummy data
$class = new StdClass;
$class->name = "Mr Class";
$clicks = 993924;
$visits = 657;

// adding stats with different value types
$Stats->setNamespace("test.namespace")
    ->addStat(".clicks.sub.count", $clicks)
    ->addStat("visits", $visits);

var_dump($Stats->getAllStats());
exit();

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

//stats grouped by tags
$StatsCollector->setNamespace("test.averages")
    ->addStat([
        'name' => "paypal_processing",
        'tags' => ['process_times']
    ], 23);


/*
 * Targeting convention
 * .path.to.namespace = absolute
 * path.to.namespace = sub-namespace relative to current namespace
 * path = leafnode in current name space
 * #path = tags ?
 */

$StatsCollector->getStatAverage("age"); // average of current namespace value
$StatsCollector->getStatsAverage(["target.one", "target.two", "target.three.*"]); //average of multuple targets (wildcard also?)

$StatsCollector->getStatSum("age"); // add all values together
$StatsCollector->getStatsSum(["target.one", "target.two", "target.three.*"]); // add all values together

$StatsCollector->getStatCount("age"); // count all indivudal stats
$StatsCollector->getStatsCount(["target.one", "target.two", "target.three.*"]); // count all individual stats

$StatsCollector->getStatsCountByTag("tag1");
$StatsCollector->getStatsCountByTags(['tag1','tag2']);


var_dump($StatsCollector->getStatAverage("test.averages.age")); // if not dot, assume current namespace
var_dump($StatsCollector->setNamespace("test.averages")->getStatAverage("height"));


?>