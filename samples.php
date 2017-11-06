<?php
require __DIR__ . '/vendor/autoload.php';


/**
 * Get an instance of the Collector
 */
$StatsCollector = Statistics\Collector::getInstance();


/**
 * Setting & Getting stats
 */

// basic usage (add to default namespace)
$StatsCollector->addStat("clicks", 45); // add stat to "root" default general namespace
$clicks = $StatsCollector->getStat("clicks"); // 45
$clicksWithNamespaceInKey = $StatsCollector->getStat("clicks", $withKeys = true); // Array ( [root.clicks] => 45 )

// define a new default namespace and add stats to it
$StatsCollector->setNamespace("website")
    ->addStat("clicks", 30)
    ->addStat("banner.views", 20); // add a sub-namespace to the current namespace (in a relative fashion)

// get single stat by relative (resolves to website.clicks due to last set namespace being "website" on line 20)
$clicks = $StatsCollector->getStat("clicks"); // 30 - the getStat() call is relative to your last default namespace

// get single stat by sub-namespace relative (resolves to website.banner.views)
$clicks = $StatsCollector->getStat("banner.views"); // 20 - the getStat() call is made to website.banner.clicks

// get single stat by absolute path
$clicks = $StatsCollector->getStat(".website.clicks"); // 30 - prepending paths with '.' resolves to absolute paths

// get multiple stats back using absolute paths
$statsAbsolute = $StatsCollector->getStats([
    '.website.clicks',
    '.website.banner.views'
]); // $statsAbsolute = Array ( [0] => 30 [1] => 20 )

// get multiple stats back using absolute paths including their full namespace as the key
$statsAbsoluteWithKeys = $StatsCollector->getStats([
    '.website.clicks',
    '.website.banner.views'
],$withKeys=true); // $statsAbsoluteWithKeys = Array ( [website.clicks] => 30 [website.banner.views] => 20 )

// get multiple stats, one using absolute namespace and one using relative namespace
$statsRelative = $StatsCollector->getStats(['clicks', '.website.banner.views']); // Array ( [0] => 30 [1] => 20 )

//define a long namespace, add a stat related stats and retrieve it using a wildcard operator
$StatsCollector->setNamespace("this.is.a.really.long.namespace.path")
    ->addStat("age", 33);
$clicks = $StatsCollector->getStat("this.*.age"); // 33

//define a namespace, add some stats and retrieve them all with wildcard paths
$StatsCollector->setNamespace("transactions")
    ->addStat("paypal", 10)
    ->addStat("ayden", 20)
    ->addStat("sagepay", 30)
    ->addStat("braintree", 40);

// lets get all transaction stats using the wildcard operator
$transactions = $StatsCollector->getStat("transactions.*");
// $transactions = Array ( [0] => 10 [1] => 20 [2] => 30 [3] => 40 )

// lets get all transaction stats using the wildcard operator including their full namespace as the key
$transactionsWithKeys = $StatsCollector->getStat("transactions.*", true);
// $transactions = Array ( [transactions.paypal] => 10 [transactions.ayden] => 20 [transactions.sagepay] => 30 [transactions.braintree] => 40 )

// getStat() and getStats() will auto-deduplicate results if you accidently include the same stat twice using wildcards
$transactionsWithUniqueStats = $StatsCollector->getStats(["transactions.*", ".transactions.paypal"]);
// only one paypal stat of '10' is present in the result $transactionsWithUniqueStats = Array ( [0] => 10 [1] => 20 [2] => 30 [3] => 40 )


/**
 * Working with stats, basic functions (increment/decrement)
 */

### lets increment some stats ###
$StatsCollector->setNamespace("general.stats")
    ->addStat("days_on_the_earth", (33 * 365))// 12045 added to 'general.stats.days_on_the_earth'
    ->incrementStat("days_on_the_earth"); //
echo $StatsCollector->getStat("days_on_the_earth") . PHP_EOL; // 12046
echo $StatsCollector->getStat(".general.stats.days_on_the_earth") . PHP_EOL; // same as above 12046

### lets decrement some stats###
$StatsCollector->setNamespace("general.other.stats")
    ->addStat("days_until_christmas", 53)// 53 as of 11/02/2017
    ->decrementStat("days_until_christmas"); // skip 24 hours
echo $StatsCollector->getStat("days_until_christmas"); // 52


/**
 * Working with stats, basic aggregate functions (increment/decrement)
 */


### lets add a bunch of stats and sum them ###
$StatsCollector->setNamespace("noahs.ark.passengers")
    ->addStat("humans", 2)
    ->addStat("aliens", 0)
    ->addStat("animal.cats", 3)// adds sub-namespace 'noahs.ark.passengers.animal.cats'
    ->addStat("animal.dogs", 6)
    ->addStat("animal.chickens", 25);

// lets get the total passenger count on noahs ark
echo $StatsCollector->getStat("noahs.ark.passengers.*") . PHP_EOL; // same as above 12046
exit();

### lets sum up some stats ##
$StatsCollector->setNamespace("donation.count")
    ->addStat("jan", 553)
    ->addStat("feb", 223)
    ->addStat("mar", 434)
    ->addStat("apr", 731)
    ->addStat("may", 136)
    ->addStat("june", 434)
    ->addStat("july", 321)
    ->addStat("aug", 353)
    ->addStat("sept", 657)
    ->addStat("oct", 575)
    ->addStat("nov", 1020)
    ->addStat("dec", 2346);

// get the total of the above stats
echo $StatsCollector->getStatsSum([
        'jan',
        'feb',
        'mar',
        'apr',
        'may',
        'june',
        'july',
        'aug',
        'sept',
        'oct',
        'nov',
        'dec'
    ]) . PHP_EOL; //7783

### Averages of a collection of stats

// lets work out the average donations per month based on the above stats
echo $StatsCollector->getStatsAverage([
        'jan',
        'feb',
        'mar',
        'apr',
        'may',
        'june',
        'july',
        'aug',
        'sept',
        'oct',
        'nov',
        'dec'
    ]) . PHP_EOL; //648.58333333333


/**
 * Compound stats usage
 *
 * Stats become "compound" when you add either an array of scalars as the value or when you add a stat to
 * an already existing namespace.
 */

### Compound Averages

// lets get the average of a compound stat
$StatsCollector->setNamespace("test.averages")
    ->addStat("age", 23)
    ->addStat("age", 12)
    ->addStat("age", 74)
    ->addStat("age", 49)
    ->addStat("age", 9);
echo $StatsCollector->getStatAverage('age') . PHP_EOL; //33.4

// another way to add or convert to a compound stat is just to pass an array of values as the value (it will auto-flatten by default)
$StatsCollector->setNamespace("test.averages")
    ->addStat("heights", 17)
    ->addStat("heights", [
        18,
        22,
        46,
        43,
        23,
        61,
        84
    ]);

echo $StatsCollector->getStatAverage('heights') . PHP_EOL; //39.25

// lets take two different compound stats and work out the collective average
$StatsCollector->setNamespace("donation.amounts")
    ->addStat("paypal", 10)
    ->addStat("paypal", 22)
    ->addStat("paypal", 16)
    ->addStat("paypal", 15)
    ->addStat("paypal", 50)
    ->addStat("ayden", 18)
    ->addStat("ayden", 22)
    ->addStat("ayden", 20)
    ->addStat("ayden", 33)
    ->addStat("ayden", 14);

echo $StatsCollector->getStatsAverage(['paypal', 'ayden']) . PHP_EOL; //22
echo $StatsCollector->getStatsAverage(['.donation.amounts.paypal', '.donation.amounts.ayden']) . PHP_EOL; //22


## Compound Count (the number of values for a given stat)

// count how many values there are in one namespace
echo $StatsCollector->getStatCount(".test.averages.age") . PHP_EOL; //5 (stats set on line 117)

// count how many values there are in a collection of namespaces
echo $StatsCollector->getStatsCount([
        ".test.averages.age",
        ".donation.amounts.paypal",
        ".donation.amounts.ayden"
    ]) . PHP_EOL; //15


## Compound Summation

// lets get the sum of a compound stat
$StatsCollector->setNamespace("gateway.tracking")
    ->addStat("timeouts", 23)
    ->addStat("timeouts", 12)
    ->addStat("timeouts", 74)
    ->addStat("timeouts", 49)
    ->addStat("timeouts", 9);

echo $StatsCollector->getStatSum('timeouts') . PHP_EOL; // 167


// lets get the combined sum of two different compound stats
$StatsCollector->setNamespace("gateway.tracking")
    ->addStat("server_errors", 23)
    ->addStat("server_errors", 12)
    ->addStat("server_errors", 74)
    ->addStat("server_errors", 49)
    ->addStat("server_errors", 9);

echo $StatsCollector->getStatsSum(['timeouts', 'server_errors']) . PHP_EOL; // 334


/**
 * Work in progress below
 */

//stats grouped by tags
//$StatsCollector->setNamespace("test.averages")
//    ->addStat([
//        'name' => "paypal_processing",
//        'tags' => ['process_times']
//    ], 23);


/*
 * Targeting convention
 * .path.to.namespace = absolute
 * path.to.namespace = sub-namespace relative to current namespace
 * path = leafnode in current name space
 * #path = tags ?
 */
//
//
//$StatsCollector->getStatsCountByTag("tag1");
//$StatsCollector->getStatsCountByTags(['tag1', 'tag2']);


?>