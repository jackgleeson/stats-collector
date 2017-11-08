<?php


namespace Statistics\Collector;

use Statistics\Exporter\iExporter;
use Dflydev\DotAccessData\Data as Container;
use Statistics\Exception\StatisticsCollectorException;

/**
 * Statistics Collector
 *
 * This utility is designed to allow simple namespace structured key/value storage for statistics recording
 * during the lifecycle of a request or process.
 *
 * Recorded statistics can then be exported via a backend specific exporter class to file, log, db, queue, other.
 *
 * Reportable stats are stored in defined namespaces. The namespace structure/convention/naming is entirely up to the
 * user e.g. queue.emails.inbox , civi.user.unsubscribed, server1.website.clicks are all acceptable
 *
 */
abstract class AbstractCollector implements iCollector
{
    /**
     * Singleton instances container
     * @var array
     */
    private static $instances = [];

    /**
     * namespace separator
     */
    const SEPARATOR = '.';

    /**
     * Wildcard operator
     */
    const WILDCARD = '*';

    /**
     * @var null|string
     */
    protected $namespace = null;
    /**
     * @var string
     */
    protected $defaultNamespace = "root";

    /**
     * Container for stats data
     * @var Container
     */
    protected $container;

    private $populatedNamespaces = [];


    /**
     * Add some Singleton visibility restrictions to avoid inconsistencies.
     */

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __sleep()
    {
        return [];
    }

    public function __wakeup()
    {
        return [];
    }

    /**
     * It is possible this singleton will be extended to allow subject specific conveniences
     * for statistics collection e.g. a fixed default namespace of "queue." in QueueStatsCollector
     *
     * @return \Statistics\Collector\ICollector
     */
    public static function getInstance()
    {
        $class = get_called_class(); // late-static-bound class name
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static;
            self::$instances[$class]->containerSetup();
        }
        return self::$instances[$class];
    }

    /**
     * Record a statistic for a subject
     *
     * TODO:
     * - workout how to handle backend specific types as values
     * @param string $name name of statistic to be added to namespace
     * @param mixed $value
     * @param array $options
     * @return \Statistics\Collector\ICollector
     */
    public function addStat($name, $value, $options = [])
    {
        // we auto-flatten any multi-dimensional arrays
        if (!array_key_exists("flatten", $options)) {
            $options['flatten'] = true;
        }

        $this->addValueToNamespace($name, $value, $options);
        return $this;
    }

    /**
     * Delete a statistic
     * @param string $namespace
     * @return \Statistics\Collector\ICollector
     * @throws StatisticsCollectorException
     */
    public function removeStat($namespace)
    {
        if (strpos($namespace, static::WILDCARD) !== false) {
            throw new StatisticsCollectorException("Wildcard usage forbidden when removing stats (to protect you from yourself!)");
        }

        if ($this->checkExists($namespace) === true) {
            $this->removeValueFromNamespace($namespace);
        } else {
            throw new StatisticsCollectorException("Attempting to remove a statistic that does not exist: " . $namespace);
        }
        return $this;
    }

    /**
     * Increment a statistic
     * @param string $namespace
     * @param int $increment
     * @return \Statistics\Collector\ICollector
     * @throws StatisticsCollectorException
     */
    public function incrementStat($namespace, $increment = 1)
    {
        if ($this->checkExists($namespace) !== true) {
            $this->addStat($namespace, 0);
        }

        $currentValue = $this->getStat($namespace);
        if ($this->is_incrementable($currentValue)) {
            $this->updateValueAtNamespace($namespace, $currentValue + $increment);
            return $this;
        } else {
            throw new StatisticsCollectorException("Attempted to increment a value which cannot be incremented! (" . $namespace . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Decrement a statistic
     * @param $namespace
     * @param int $decrement
     * @return \Statistics\Collector\ICollector
     * @throws StatisticsCollectorException
     */
    public function decrementStat($namespace, $decrement = -1)
    {
        if ($this->checkExists($namespace) !== true) {
            $this->addStat($namespace, 0);
        }

        $currentValue = $this->getStat($namespace);
        if ($this->is_incrementable($currentValue)) {
            $this->updateValueAtNamespace($namespace, $currentValue - abs($decrement));
            return $this;
        } else {
            throw new StatisticsCollectorException("Attempted to decrement a value which cannot be decremented! (" . $namespace . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Retrieve statistic for a given namespace
     * @param string $namespace
     * @param bool $withKeys
     * @param mixed $default default value to be returned if stat $namespace empty
     * @return mixed
     */
    public function getStat($namespace, $withKeys = false, $default = null)
    {
        // send wildcards and multi-namespaces to the plural method
        if (strpos($namespace, static::WILDCARD) !== false ||
            is_array($namespace)
        ) {
            return $this->getStats([$namespace], $withKeys, $default);
        }

        if ($this->checkExists($namespace) === true) {
            $resolvedNamespace = $this->getTargetNamespaces($namespace);

            if ($withKeys === true) {
                $value[$resolvedNamespace] = $this->getValueFromNamespace($namespace);
            } else {
                $value = $this->getValueFromNamespace($namespace);
            }
        } else {
            if ($withKeys === true) {
                $value[$namespace] = $default;
            } else {
                $value = $default;
            }
        }
        return $value;

    }

    /**
     * Retrieve a collection of statistics with an array of subject namespaces
     * @param array $namespaces
     * @param bool $withKeys
     * @param mixed $default default value to be returned if stat $namespace empty
     * @return array
     */
    public function getStats(array $namespaces, $withKeys = false, $default = null)
    {
        $resolvedNamespaces = $this->getTargetNamespaces($namespaces, true);
        if (!is_array($resolvedNamespaces)) {
            $resolvedNamespaces = [$resolvedNamespaces];
        }

        //iterate over $namespaces and retrieve values
        $stats = [];
        foreach ($resolvedNamespaces as $namespace) {
            $stat = $this->getStat($namespace, $withKeys, $default);
            $stats = array_merge($stats, (is_array($stat) ? $stat : [$stat]));
        }
        return $stats;
    }

    /**
     * Count the number of values recorded for a given stat
     * @param $namespace
     * @return int
     */
    public function getStatCount($namespace)
    {
        $value = $this->getStat($namespace);
        return count($value);
    }

    /**
     * Count the number of values recorded for a collection of given stats
     * @param array $namespaces
     * @return int
     * @internal param array $names
     */
    public function getStatsCount(array $namespaces)
    {
        $count = 0;
        foreach ($namespaces as $namespace) {
            $count += $this->getStatCount($namespace);
        }
        return $count;
    }

    /**
     * @param $namespace
     * @return float|int
     */
    public function getStatAverage($namespace)
    {
        $value = $this->getStat($namespace);
        return $this->calculateStatsAverage($value);
    }

    /**
     * @param array $namespaces
     * @return float|int
     */
    public function getStatsAverage(array $namespaces)
    {
        $allStats = [];
        foreach ($namespaces as $namespace) {
            $value = $this->getStat($namespace);
            if (!is_array($value)) {
                $value = [$value];
            }
            $allStats = array_merge($allStats, $value);
        }
        return $this->calculateStatsAverage($allStats);

    }

    /**
     * @param $namespace
     * @return float|int
     */
    public function getStatSum($namespace)
    {
        $value = $this->getStat($namespace);
        return $this->calculateStatsSum($value);
    }

    /**
     * @param array $namespaces
     * @return float|int
     */
    public function getStatsSum(array $namespaces)
    {
        $totalSum = [];
        foreach ($namespaces as $namespace) {
            $values = $this->getStat($namespace);
            if (!is_array($values)) {
                $values = [$values];
            }
            $totalSum = array_merge($totalSum, $values);
        }
        return $this->calculateStatsSum($totalSum);
    }

    /**
     *  Retrieve statistics for all subject namespaces
     *
     * TODO:
     * - take array of namespaces with wildcards to target specific namespaces
     * @param string $namespace
     * @return array
     * @throws StatisticsCollectorException
     */
    public function getAllStats($namespace = "*")
    {
        if ($namespace === "*") {
            $data = [];
            foreach ($this->populatedNamespaces as $namespace) {
                $data[$namespace] = $this->container->get($namespace);
            }
            return $data;
        } else {
            throw new StatisticsCollectorException("Not currently implemented!");
        }
    }

    /**
     * Export statistics using backend specific $Exporter
     *
     * TODO:
     * - take array of namespaces to target specific namespaces
     *
     * @param string $namespaces
     * @param iExporter $Exporter
     * @return
     * @throws StatisticsCollectorException
     */
    public function export($namespaces = "*", iExporter $Exporter)
    {
        if ($namespaces === "*") {
            return $Exporter->export($this->getAllStats());
        } else {
            throw new StatisticsCollectorException("Not currently implemented!");

        }
    }


    /**
     * @param $namespace
     * @return \Statistics\Collector\ICollector
     */
    public function setNamespace($namespace)
    {
        return $this->setCurrentNamespace($namespace);

    }

    /**
     * Return the current namespace. Default to default namespace if none set.
     * @return string
     */
    public function getCurrentNamespace()
    {
        return ($this->namespace === null) ? $this->getDefaultNamespace() : $this->namespace;
    }

    /**
     * @return array
     */
    protected function getPopulatedNamespaces()
    {
        return $this->populatedNamespaces;
    }

    /**
     * TODO:
     * - validate namespace argument
     * @param $namespace
     * @return \Statistics\Collector\ICollector
     */
    protected function setCurrentNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string $namespace
     * @return mixed
     */
    protected function resolveWildcardNamespace($namespace)
    {
        // clear absolute path initial '.' as not needed for wildcard
        if (strpos($namespace, static::SEPARATOR) === 0) {
            $namespace = $target = substr($namespace, 1);
        }

        $expandedPaths = [];
        foreach ($this->getPopulatedNamespaces() as $populatedNamespace) {
            if (fnmatch($namespace, $populatedNamespace)) {
                // we convert the expanded wildcard path to an absolute path by prepending '.'
                // this prevents the namespace resolution method from treating the full namespace as a sub namespace
                $expandedPaths[] = static::SEPARATOR . $populatedNamespace;
            }
        }

        return $expandedPaths;
    }

    /**
     * Determine the target namespace(s) based on the namespace value(s)
     * '.' present at beginning indicates absolute namespace path
     * '.' present but not at the beginning indicates branch namespace path of the current namespace
     * '.' not present indicates leaf-node namespace of current namespace
     * '*' present indicates wildcard namespace path expansion required
     * @param mixed $namespaces
     * @param bool $returnAbsolute
     * @return mixed $resolvedNamespaces
     */
    protected function getTargetNamespaces($namespaces, $returnAbsolute = false)
    {
        if (!is_array($namespaces)) {
            $namespaces = [$namespaces];
        }

        $resolvedNamespaces = [];
        foreach ($namespaces as $namespace) {
            if (strpos($namespace, static::WILDCARD) !== false) {
                // wildcard
                $wildcardPaths = $this->resolveWildcardNamespace($namespace);
                $resolvedNamespaces = array_merge($resolvedNamespaces, $wildcardPaths);
            } else {
                // non-wildcard
                if (strpos($namespace, static::SEPARATOR) === 0) {
                    // absolute path namespace e.g. '.this.a.full.path.beginning.with.separator'
                    $resolvedNamespaces[] = ($returnAbsolute === false) ? substr($namespace, 1) : $namespace;
                } else {
                    // leaf-node namespace of current namespace e.g. 'dates' or
                    // sub-namespace e.g 'sub.path.of.current.namespace'
                    $resolvedNamespaces[] = ($returnAbsolute === false) ?
                        $this->getCurrentNamespace() . static::SEPARATOR . $namespace :
                        static::SEPARATOR . $this->getCurrentNamespace() . static::SEPARATOR . $namespace;
                }
            }
        }

        return (count($resolvedNamespaces) === 1) ? $resolvedNamespaces[0] : array_unique($resolvedNamespaces);
    }

    /**
     * @param string $namespace
     * @param mixed $value
     * @param array $options
     * @return \Statistics\Collector\ICollector
     */
    protected function addValueToNamespace($namespace, $value, $options)
    {
        if (array_key_exists("flatten", $options) &&
            $options['flatten'] === true &&
            is_array($value)
        ) {
            $flatten = true;
            $flattenedValues = $this->arrayFlatten($value);
        }

        $targetNS = $this->getTargetNamespaces($namespace);

        if ($this->container->has($targetNS)) {
            if (isset($flatten) && $flatten === true) {
                $currentValue = $this->container->get($targetNS);
                $values = (is_array($currentValue)) ?
                    array_merge($currentValue, $flattenedValues) : array_merge([$currentValue], $flattenedValues);
                $this->container->set($targetNS, $values);
            } else {
                $this->container->append($targetNS, $value);
            }
        } else {
            $this->container->set($targetNS, $value);
            $this->addPopulatedNamespace($targetNS);
        }
        return $this;
    }

    /**
     * @param $namespace
     * @param $value
     * @return \Statistics\Collector\ICollector
     * @throws StatisticsCollectorException
     * @internal param $name
     */
    protected function updateValueAtNamespace($namespace, $value)
    {
        $targetNS = $this->getTargetNamespaces($namespace);
        if ($this->container->has($targetNS)) {
            $this->container->set($targetNS, $value);
        } else {
            throw new StatisticsCollectorException("Unable to update value at " . $targetNS);
        }
        return $this;
    }

    /**
     * @param $name
     * @return \Statistics\Collector\ICollector
     */
    protected function removeValueFromNamespace($name)
    {
        $targetNS = $this->getTargetNamespaces($name);
        $this->container->remove($targetNS);
        $this->removePopulatedNamespace($targetNS);
        return $this;
    }

    /**
     * Retrieve stats value from container, return null if not found.
     * @param $name
     * @return mixed
     */
    protected function getValueFromNamespace($name)
    {
        $targetNS = $this->getTargetNamespaces($name);
        return $this->container->get($targetNS);
    }

    /**
     * @return string
     */
    protected function getDefaultNamespace()
    {
        return $this->defaultNamespace;
    }

    /**
     * Check to see if value can be incremented.
     * Currently PHP only allows numbers and strings to be incremented. We only want numbers
     *
     * @param mixed $value
     * @return bool
     */
    protected function is_incrementable($value)
    {
        return (is_int($value) || is_float($value));
    }

    /**
     * Keep track of populated namespaces
     * @param $namespace
     * @return bool
     */
    protected function addPopulatedNamespace($namespace)
    {
        array_push($this->populatedNamespaces, $namespace);
        return true;
    }

    /**
     * Remove a namespace from the populated namespaces array (typically when it becomes empty)
     * @param $namespace
     * @return bool
     */
    protected function removePopulatedNamespace($namespace)
    {
        if (($index = array_search($namespace, $this->populatedNamespaces)) !== false) {
            unset($this->populatedNamespaces[$index]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check that namespace element(s) exist
     *
     * TODO: write the value of the non-existent namespace for arrays of namespaces out somewhere
     * @param mixed $namespace
     * @return bool
     */
    protected function checkExists($namespace)
    {
        $resolvedNamespace = $this->getTargetNamespaces($namespace);
        if (is_array($resolvedNamespace)) {
            foreach ($resolvedNamespace as $ns) {
                if (!$this->container->has($ns)) {
                    return false;
                }
            }
        } else {
            if (!$this->container->has($resolvedNamespace)) {
                return false;
            }
        }
        return true;
    }

    protected function calculateStatsSum($stats)
    {
        if ($this->is_summable($stats)) {
            switch (gettype($stats)) {
                case "integer":
                case "float":
                    return $stats;
                case "array":
                    return $this->sum($stats);
                default:
                    throw new StatisticsCollectorException("Unable to return sum for this collection of values (are they all numbers?)");
            }
        } else {
            throw new StatisticsCollectorException("Unable to return sum for this collection of values (are they all numbers?)");
        }

    }

    protected function calculateStatsAverage($stats)
    {
        if ($this->is_averageable($stats)) {
            switch (gettype($stats)) {
                case "integer":
                case "float":
                    return $stats;
                case "array":
                    return $this->average($stats);
                default:
                    throw new StatisticsCollectorException("Unable to return average for this collection of values (are they all numbers?)");
            }
        } else {
            throw new StatisticsCollectorException("Unable to return average for this collection of values (are they all numbers?)");
        }
    }

    /**
     * TODO:
     * - this is the same as is averageable(). refactor both into one method?
     * @param mixed $value
     * @return bool
     */
    protected function is_summable($value)
    {
        switch (gettype($value)) {
            case "integer":
            case "float":
                return true;
            case "array":
                foreach ($value as $v) {
                    if ($this->is_summable($v) === false) {
                        return false;
                    }
                }
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if value is a number or a collection of numbers available to averaged.
     *
     * TODO:
     * - work out how to prevent subnamespaces of the current breaking current averaging
     * @param $value
     * @return bool
     */
    protected function is_averageable($value)
    {
        switch (gettype($value)) {
            case "integer":
            case "float":
                return true;
            case "array":
                foreach ($value as $v) {
                    if ($this->is_averageable($v) === false) {
                        return false;
                    }
                }
                return true;
            default:
                return false;
        }
    }

    /**
     * Get the average of a collection of values
     * @param array $values
     * @return float|int
     */
    protected function average($values = [])
    {
        return array_sum($values) / count($values);
    }

    /**
     * Get the sum of a collection of values
     * @param array $values
     * @return float|int
     */
    protected function sum($values = [])
    {
        return array_sum($values);
    }

    /**
     * Flatten a multi-dimensional array down to a single array
     * @param array $array
     * @return array
     */
    protected function arrayFlatten($array = [])
    {
        $flattened = [];
        array_walk_recursive($array, function ($a) use (&$flattened) {
            $flattened[] = $a;
        });
        return $flattened;
    }

    /**
     * During getInstance() we want to configure the container to be an instance of Container()
     */
    protected function containerSetup()
    {
        if (!$this->container instanceof Container) {
            $this->container = new Container();
        }
    }

}