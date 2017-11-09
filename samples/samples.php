<?php
require __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';
/**
 * Get an instance of the Collector
 */
$statsCollector = Statistics\Collector\Collector::getInstance();

/**
 * Setting & Getting stats
 */

// basic usage (add to default namespace)
$statsCollector->addStat("clicks",
  45); // add stat to "general" default general namespace
$clicks = $statsCollector->getStat("clicks"); // 45
$clicksWithNamespaceInKey = $statsCollector->getStat("clicks",
  $withKeys = true); // Array ( [general.clicks] => 45 )

// define a new default namespace and add stats to it
$statsCollector->setNamespace("website")
  ->addStat("clicks", 30)
  ->addStat("banner.views",
    20); // add a sub-namespace to the current namespace (in a relative fashion)

// get single stat by relative (resolves to website.clicks due to last set namespace being "website" on line 18)
$clicks = $statsCollector->getStat("clicks"); // 30 - the getStat() call is relative to your last default namespace

// get single stat by sub-namespace relative (resolves to website.banner.views)
$bannerViews = $statsCollector->getStat("banner.views"); // 20 - the getStat() call is made to website.banner.clicks

// get single stat by absolute path
$websiteClicks = $statsCollector->getStat(".website.clicks"); // 30 - prepending paths with '.' resolves to an absolute path

// get multiple stats back using absolute paths
$statsAbsolute = $statsCollector->getStats([
  '.website.clicks',
  '.website.banner.views',
]); // $statsAbsolute = Array ( [0] => 30 [1] => 20 )

// get multiple stats back using absolute paths including their full namespace as the key
$statsAbsoluteWithKeys = $statsCollector->getStats([
  '.website.clicks',
  '.website.banner.views',
],
  $withKeys = true); // $statsAbsoluteWithKeys = Array ( [website.clicks] => 30 [website.banner.views] => 20 )

// get multiple stats, one using absolute namespace and one using relative namespace
$statsRelative = $statsCollector->getStats([
  'clicks',
  '.website.banner.views',
]); // Array ( [0] => 30 [1] => 20 )

//removing a stat
$statsCollector->removeStat('clicks');

//define a long namespace, add a stat related stats and retrieve it using a wildcard operator
$statsCollector->setNamespace("this.is.a.really.long.namespace.path")
  ->addStat("age", 33);
$clicks = $statsCollector->getStat("this.*.age"); // 33

//define a namespace, add some stats and retrieve them all with wildcard paths
$statsCollector->setNamespace("transactions")
  ->addStat("mobile", 10)
  ->addStat("website", 20)
  ->addStat("tablet", 30)
  ->addStat("other", 40);

// lets get all transaction stats using the wildcard operator
$transactions = $statsCollector->getStat("transactions.*");
// $transactions = Array ( [0] => 10 [1] => 20 [2] => 30 [3] => 40 )

// lets get all transaction stats using the wildcard operator including their full namespace as the key
$transactionsWithKeys = $statsCollector->getStat("transactions.*", true);
// $transactions = Array ( [transactions.mobile] => 10 [transactions.website] => 20 [transactions.tablet] => 30 [transactions.other] => 40 )


// getStat() and getStats() will auto-deduplicate results if you accidently include the same stat twice using wildcards
$transactionsWithUniqueStats = $statsCollector->getStats([
  "transactions.*",
  ".transactions.mobile",
]);
// only one mobile stat of '10' is present in the result $transactionsWithUniqueStats = Array ( [0] => 10 [1] => 20 [2] => 30 [3] => 40 )


/**
 * Working with stats, basic functions (increment/decrement)
 */

// lets increment some stats
$statsCollector->setNamespace("general.stats")
  ->addStat("days_on_the_earth",
    (33 * 365))// 12045 added to 'general.stats.days_on_the_earth'
  ->incrementStat("days_on_the_earth", 5); // we time travel forward 24 hours.
$daysOnEarth = $statsCollector->getStat("days_on_the_earth"); // 12046
$daysOnEarthAbsolute = $statsCollector->getStat(".general.stats.days_on_the_earth"); // same as above 12046

// lets decrement some stats
$statsCollector->setNamespace("general.other.stats")
  ->addStat("days_until_christmas", 53)// 53 as of 11/02/2017
  ->decrementStat("days_until_christmas"); // skip 24 hours
$daysUntilChristmas = $statsCollector->getStat("days_until_christmas"); // 52

/**
 * Working with stats, aggregate functions (sum/average)
 */

// lets add a bunch of stats and sum them
$statsCollector->setNamespace("noahs.ark.passengers")
  ->addStat("humans", 2)
  ->addStat("aliens", 0)
  ->addStat("animal.cats",
    3)// adds sub-namespace 'noahs.ark.passengers.animal.cats'
  ->addStat("animal.dogs", 6)
  ->addStat("animal.chickens", 25);

// total number of passengers on noahs ark
$numberOfPassengers = $statsCollector->getStatSum("noahs.ark.passengers.*"); // 36

// lets sum up some individual stats
$statsCollector->setNamespace("visits.month")
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
$visitsForTheYear = $statsCollector->getStatsSum([
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
  'dec',
]); //7783

// you could also use a wildcard to get the sum of visits by targeting  'visits.month.*'
$visitsForTheYearWildcard = $statsCollector->getStatSum("visits.month.*"); ////7783

// lets work out the average visits per month based on the above stats
$averageVisitsPerMonth = $statsCollector->getStatsAverage([
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
  'dec',
]); //648.58333333333

$averageVisitsPerMonthWildcard = $statsCollector->getStatAverage("visits.month.*"); //648.58333333333


/**
 * Working with compound stats (averages/sum/count)
 *
 * Stats become "compound" when you add either an array of values to a single
 * stat or when you add a stat to an already existing namespace.
 */

// lets get the average of a compound stat
$statsCollector->setNamespace("users")
  ->addStat("age", 23)
  ->addStat("age", 12)
  ->addStat("age", 74)
  ->addStat("age", 49)
  ->addStat("age", 9);
$averageAges = $statsCollector->getStatAverage('age'); //33.4

// another way to convert to a compound stat is just to pass an array of values as the value (it will auto-flatten by default)
$statsCollector->setNamespace("users")
  ->addStat("heights", 171)
  ->addStat("heights", [
    181,
    222,
    194,
    143,
    123,
    161,
    184,
  ]);

$averageHeights = $statsCollector->getStatAverage('heights'); //172.375

// lets take three different compound stats and work out the collective sum
$statsCollector->setNamespace("website.referrals")
  ->addStat("google", 110)
  ->addStat("google", 222)
  ->addStat("google", 146)
  ->addStat("google", 125)
  ->addStat("yahoo", 510)
  ->addStat("yahoo", 148)
  ->addStat("yahoo", 2122)
  ->addStat("bing", 230)
  ->addStat("bing", 335)
  ->addStat("bing", 141);

$totalReferrals = $statsCollector->getStatsSum([
  'google',
  'yahoo',
  'bing',
]); // 4089

// lets take three different compound stats and work out the collective sum by usng absolute namespace paths
$totalReferralsAbsolute = $statsCollector->getStatsSum([
  '.website.referrals.google',
  '.website.referrals.yahoo',
  '.website.referrals.bing',
]); // 4089


// Lets count how many values there are in a namespace
// (count will return the number of values, not the sum of the values)
$googleReferralEntryCount = $statsCollector->getStatCount(".website.referrals.google"); //4

// count how many values there are in a collection of namespaces at once
$totalReferralEntries = $statsCollector->getStatsCount([
  ".website.referrals.google",
  ".website.referrals.yahoo",
  ".website.referrals.bing",
]); //15

// lets get the sum of a compound stat
$statsCollector->setNamespace("api.response")
  ->addStat("success", 23223)
  ->addStat("success", 1322)
  ->addStat("success", 7324)
  ->addStat("success", 24922)
  ->addStat("success", 94234);

$totalSuccessfulResponses = $statsCollector->getStatSum('.api.response.success'); // 151025

// lets get the combined sum of two different compound stats
$statsCollector->setNamespace("api.response")// we don't need to redeclare this unless the namespace changes
->addStat("error", 23)
  ->addStat("error", 12)
  ->addStat("error", 74)
  ->addStat("error", 49)
  ->addStat("error", 9);

$totalResponses = $statsCollector->getStatsSum([
  '.api.response.success',
  '.api.response.error',
]); // 151192


/**
 * Extending the Stats Collector with your own subject specific instance is
 * also possible by extending the AbstractCollector
 */

$CiviCRMCollector = Samples\CiviCRMCollector::getInstance();
$CiviCRMCollector->addStat("users.created", 500);
$usersCreated = $CiviCRMCollector->getStat("users.created");


/**
 * Exporting stats to Prometheus exporter
 */

//export all stats collected so far to sample_stats.prom file
//exporter also takes care of any mapping required for output. In the case of
//Prometheus, we map dots to underscores before writing to .prom files.
$exporter = new Statistics\Exporter\Prometheus("sample_stats");
$exporter->path = __DIR__ . DIRECTORY_SEPARATOR . 'prometheus_out'; // output path
$exporter->export($statsCollector);

// export a bunch of targeted stats
// you can update $exporter->filename & $exporter->path before each export() call for a different output dir/name
$exporter->filename = "noahs_ark_stats";

// return as associative array of namespace=>value to pass to export() due to $withKeys=true being passed
$noahsArkStats = $statsCollector->getStat("noahs.ark.passengers.*", true);
$exporter->export($noahsArkStats);

//export a custom collector instance
$exporter->filename = "civicrm_stats";
$exporter->export($CiviCRMCollector);


?>