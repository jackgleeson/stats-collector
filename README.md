# Stats Collector

This utility allows you to record, retrieve and export custom statistics during the lifecycle of any PHP process. 

Once you have recorded some stats, you can create new stats from your data using aggregate analysis with traditional functions like average, count and sum. You can export your stats to an output of your choice, e.g. file, log, db, queue or other custom formats. Finally, you can then display and query the exported stats in whatever frontend you wish, e.g. grafana. 

### To-do
  - Import behaviour. Allow Stats Collector to import previously exported data and carry on where it left off. 
  - Add tests for helpers and improve exporter tests by mocking Collector
### Credits

* [github.com/dflydev/dflydev-dot-access-data](https://github.com/dflydev/dflydev-dot-access-data)  - small but powerful dot namesapce utility

### Add Stats Collector to your project
```sh
$ composer require jackgleeson/stats-collector 
```
### Basic Usage: record, increment and retrieve a stat
```php
//get an instance of stats collector
$stats = Statistics\Collector\Collector::getInstance();

//add a stat
$stats->add("clicks", 45);

//get a stat
$clicks = $stats->get("clicks");
$clicks; // 45

//increment a stat 
$stats->inc("clicks");
$clicks = $stats->get("clicks");
$clicks; // 46
```
### Basic Usage: Custom stats namespace and wildcard operator usage
```php
$stats = Statistics\Collector\Collector::getInstance();

//add a custom namespace and add some stats to it
$stats->ns("crons.payments")
  ->add("total", 30)
  ->add("succeeded", 20)
  ->add("failed", 10);

// get payment cron stats using wildcard options
$paymentStats = $stats->getWithKey("crons.payments.*");
$paymentStats = $stats->getWithKey("*.payments.*"); // same result as above
$paymentStats = $stats->getWithKey("*payments*"); // same result as above

// $paymentStats contents
Array
(
    [crons.payments.failed] => 10
    [crons.payments.succeeded] => 20
    [crons.payments.total] => 30
)
```
### Basic Usage: Record the execution time of a process

```php
$stats = Statistics\Collector\Collector::getInstance();

$stats->ns("timer")->add("start", microtime(true));
// some lengthy process
$stats->ns("timer")->add("end", microtime(true));

$execution_time = $stats->ns("timer")->get("end") - $stats->ns("timer")->get("start");

// or if you wanted to export the execution time, you could do this:
$stats->ns("timer")->add('execution_time', $stats->get("end") - $stats->get("start"));
```
### Basic Usage: Export stats to file
```php
$stats = Statistics\Collector\Collector::getInstance();

//add a custom namespace and add some stats to it
$stats->ns("crons.payments")
  ->add("total", 30)
  ->add("succeeded", 20)
  ->add("failed", 10);
  
//export recorded stats to a txt file (see output below)
$exporter = new Statistics\Exporter\File("demo","outdir/dir");
$exporter->export($stats);
```
### Basic Usage: Export stats to file (output)
```sh
$ cd output/dir
$ cat demo.stats
crons.payments.failed=10
crons.payments.succeeded=20
crons.payments.total=30
```

### Aggregate Usage: Add a bunch of stats across different namespaces and sum them
```php
$stats = Statistics\Collector\Collector::getInstance();

$stats->ns("noahs.ark.passengers")
  ->add("humans", 2)
  ->add("aliens", 0)
  ->add("animal.cats", 3)
  ->add("animal.dogs", 6)
  ->add("animal.chickens", 25);
  
// total number of passengers on noahs ark
$totalPassengers = $stats->sum("noahs.ark.passengers.*"); // 36
$totalAnimalPassengers = $stats->sum("*.passengers.animal.*"); // 34
```

### Aggregate Usage: Create a compound stat and work out its average
```php
$stats = Statistics\Collector\Collector::getInstance();

$stats->ns("users")
  ->add("heights", 171)
  ->add("heights", [181, 222, 194, 143, 123, 161, 184]);

$averageHeights = $stats->avg('heights'); //172.375
```


## Checkout [samples/shorthand-samples.php](https://github.com/jackgleeson/stats-collector/blob/master/samples/shorthand-samples.php) for a complete list of available functionality in action. 