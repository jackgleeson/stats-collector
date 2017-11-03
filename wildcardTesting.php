<?php
$paths = [
    "general.clicks",
    "this.is.an.absolute.namespace.path.clicks",
    "noahs.ark.passengers.humans",
    "noahs.ark.passengers.aliens",
    "noahs.ark.passengers.animal.cats",
    "noahs.ark.passengers.animal.dogs",
    "noahs.ark.passengers.animal.chickens",
    "general.stats.days_on_the_earth",
    "general.other.stats.days_until_christmas",
    "donation.count.jan",
    "donation.count.feb",
    "donation.count.mar",
    "donation.count.apr",
    "donation.count.may",
    "donation.count.june",
    "donation.count.july",
    "donation.count.aug",
    "donation.count.sept",
    "donation.count.oct",
    "donation.count.nov",
    "donation.count.dec",
    "test.averages.age",
    "test.averages.heights",
    "donation.amounts.paypal",
    "donation.amounts.ayden",
    "gateway.tracking.timeouts",
    "gateway.tracking.server_errors"
];

foreach ($paths as $path) {
    if (fnmatch($argv[1], $path)) {
        echo $path . " matches " . $argv[1] . PHP_EOL;
    } else {
        //echo $path . " does not match " . $argv[1] . PHP_EOL;
    }
}