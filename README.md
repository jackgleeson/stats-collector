# Stats Collector

This utility allows you to capture custom statistics during any PHP process. Once you have captured some stats you can then perform aggregate analysis using common functions like average(), count() and sum(). Finally you can export your stats to a backend of your choice  e.g. file, log, db, queue, Prometheus, other..

### To-do
  - Finish README
  - Import() - allow Stats Collector to import previously exported data and carry on where it left off. 
  - add getOverallStatsCount() behaviour
  - investigate custom export formats e.g. google analytics and other tools
### Credits

* [github.com/dflydev/dflydev-dot-access-data](https://github.com/dflydev/dflydev-dot-access-data)  - small but powerful dot namesapce utility

### Add Stats Collector to your project
```sh
$ composer require jackgleeson/stats-collector 
$ composer install
```
### Basic Usage
```php
//get an instance of stats collector
$stats = Statistics\Collector\Collector::getInstance();

//add a stat
$stats->add("clicks", 45);
$clicks = $stats->get("clicks");
echo $clicks; // 45

//add a custom namespace and add some stats to it
$stats->ns("crons.payment_stats")
  ->add("payments", 30)
  ->add("succeeded", 20)
  ->add("failed", 10);

// get payment cron stats using wildcard
$paymentCronStats = $stats->getWithKey("crons.payment_stats.*");
print_r($paymentCronStats);
/*
 Array
(
  [crons.payment_stats.failed] => 10
  [crons.payment_stats.payments] => 30
  [crons.payment_stats.succeeded] => 20
)
 */

//export recorded stats to a txt file (see output below)
$exporter = new Statistics\Exporter\File("demo","outdir/dir");
$exporter->export($stats);
```
### Inspect exported stats
```sh
$ cd output/dir
$ cat demo.stats
crons.payment_stats.failed=10
crons.payment_stats.payments=30
crons.payment_stats.succeeded=20
```

Checkout *samples/shorthand-samples.php* and *samples/samples.php* for full coverage of available functionaluty in action. 
