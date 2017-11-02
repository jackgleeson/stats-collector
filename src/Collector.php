<?php


namespace Statistics;

use Statistics\Exporter\ExporterInterface;
use Dflydev\DotAccessData\Data as Container;
use Statistics\Exceptions\StatisticsCollectorException;

/**
 * Statistics Collector
 *
 * This object is intended to serve as storage for application-wide statistics
 * captured during the lifecycle of a request. Recorded statistics can then be exported
 * via a backend specific exporter class to file, log, db, queue, other.
 *
 * Reportable subjects are defined as custom namespaces. The identifier namespace is
 * entirely up to the user e.g. queue.donations.received or civi.user.unsubscribed
 *
 * TODO:
 * - implement exporter strategy object to hanldle backend specific export/output logic (Prometheus being the first)
 * - add updateStat behaviour
 * - add $additionalOptions to addStat method custom backend specific tags
 * - crying out for wildcard usage in namespaces
 * - finish compound stats methods
 *
 */
class Collector
{
    /**
     * Singleton instances container
     * @var array
     */
    private static $instances = [];

    /**
     * namespace separator
     */
    protected const SEPARATOR = '.';

    /**
     * @var null|string
     */
    protected $namespace = null;
    /**
     * @var string
     */
    protected $defaultNamespace = "general";

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

    private function __sleep()
    {
    }

    private function __wakeup()
    {
    }

    /**
     * It is possible this container singleton will be extended to allow subject specific conveniences
     * for statistics collection e.g. a fixed default namespace of "queue." in QueueStatsCollector
     *
     * @return Collector
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
     * - workout how to handle backend specific types
     * @param string $name name of statistic to be added to namespace
     * @param string $value
     * @param array $additionalOptions
     * @return Collector
     */
    public function addStat($name, $value, $additionalOptions = [])
    {
        $this->addValueToNamespace($name, $value, $additionalOptions);
        return $this;
    }

    /**
     * Delete a statistic
     * @param $name
     * @return Collector
     */
    public function removeStat($name)
    {
        $this->removeValueFromNamespace($name);
        return $this;
    }

    /**
     * Increment a statistic
     *
     * @param string $name name of statistic to be added to namespace
     * @param int $increment
     * @return Collector
     * @throws StatisticsCollectorException
     */
    public function incrementStat($name, $increment = 1)
    {
        $this->checkExists($name);
        $currentValue = $this->getValueFromNamespace($name);
        if ($this->is_incrementable($currentValue)) {
            $this->updateValueAtNamespace($name, $currentValue + $increment);
            return $this;
        } else {
            throw new StatisticsCollectorException("Attemped to increment a value which cannot be incremented! (" . $name . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Decrement a statistic
     *
     * @param string $name name of statistic to be added to namespace
     * @param int $decrement
     * @return Collector
     * @throws StatisticsCollectorException
     */
    public function decrementStat($name, $decrement = -1)
    {
        $this->checkExists($name);
        $currentValue = $this->getValueFromNamespace($name);
        if ($this->is_incrementable($currentValue)) {
            $this->updateValueAtNamespace($name, $currentValue + $decrement);
            return $this;
        } else {
            throw new StatisticsCollectorException("Attemped to decrement a value which cannot be decremented! (" . $name . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Retrieve statistic for a given subject namespace
     * @param $name name of statistic to be added to namespace
     * @return mixed
     */
    public function getStat($name)
    {
        return $this->getValueFromNamespace($name);
    }

    /**
     * Retrieve a collection of statistics with an array of subject namespaces
     * @param array $names
     * @param bool $withKeys
     * @return array
     */
    public function getStats($names = [], $withKeys = false)
    {
        $values = [];
        foreach ($names as $name) {
            if ($withKeys === true) {
                $values[$this->determineTargetNS($name)] = $this->getStat($name);
            } else {
                $values[] = $this->getStat($name);
            }
        }
        return $values;
    }

    /**
     * Count the number of values of a given stat
     * @param $name
     * @return int
     */
    public function getStatCount($name)
    {
        $this->checkExists($name);
        $value = $this->getValueFromNamespace($name);
        return count($value);
    }

    /**
     * Count the number of values of a collection given stats
     * @param array $names
     * @return int
     */
    public function getStatsCount($names = [])
    {
        $allStats = [];
        foreach ($names as $name) {
            $values = $this->getValueFromNamespace($name);
            if (gettype($values) !== "array") {
                $values = [$values];
            }
            $allStats = array_merge($allStats, $values);
        }
        return count($allStats);

    }

    /**
     * @param $name
     * @return mixed
     * @throws StatisticsCollectorException
     */
    public function getStatAverage($name)
    {
        $this->checkExists($name);
        $value = $this->getValueFromNamespace($name);
        return $this->calculateStatsAverage($value);
    }

    /**
     * @param array $names
     * @return float|int
     */
    public function getStatsAverage($names = [])
    {
        $allStats = [];
        foreach ($names as $name) {
            $values = $this->getValueFromNamespace($name);
            if (gettype($values) !== "array") {
                $values = [$values];
            }
            $allStats = array_merge($allStats, $values);
        }
        return $this->calculateStatsAverage($allStats);

    }

    /**
     * @param $name
     * @return float|int
     */
    public function getStatSum($name)
    {
        $this->checkExists($name);
        $values = $this->getValueFromNamespace($name);
        return $this->calculateStatsSum($values);
    }

    /**
     * @param array $names
     * @return float|int
     */
    public function getStatsSum($names = [])
    {
        $totalSum = [];
        foreach ($names as $name) {
            $values = $this->getValueFromNamespace($name);
            if (gettype($values) !== "array") {
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
     * @param ExporterInterface $Exporter
     * @return
     * @throws StatisticsCollectorException
     */
    public function export($namespaces = "*", ExporterInterface $Exporter)
    {
        if ($namespaces === "*") {
            return $Exporter->export($this->getAllStats());
        } else {
            throw new StatisticsCollectorException("Not currently implemented!");

        }
    }


    /**
     * @param $namespace
     * @return Collector
     */
    public function setNamespace($namespace)
    {
        return $this->setCurrentNamespace($namespace);

    }

    /**
     * TODO:
     * - validate namespace argument
     * @param $namespace
     * @return Collector
     */
    public function setCurrentNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
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
    public function getPopulatedNamespaces()
    {
        return $this->populatedNamespaces;
    }

    /**
     * Determine the type of target based on the namespace value
     * '.' present at beginning indicates absolute namespace path
     * '.' present but not at the beginning indicates branch namespace path of the current namespace
     * '.' not present indicates leaf-node namespace of current namespace
     * @param $namespace
     * @return string
     */
    protected function determineTargetNS($namespace)
    {
        if (($pos = strpos($namespace, static::SEPARATOR)) !== false) {
            if ($pos === 0) {
                //absolute path namespace e.g. '.this.a.full.path.beginning.with.separator'
                $target = substr($namespace, 1);
            } else {
                //sub-namespace e.g 'sub.path.of.current.namespace'
                $target = $this->getCurrentNamespace() . static::SEPARATOR . $namespace;
            }

        } else {
            // leaf-node namespace of current namespace e.g. 'dates'
            $target = $this->getCurrentNamespace() . static::SEPARATOR . $namespace;
        }
        return $target;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return bool
     */
    protected function addValueToNamespace($name, $value, $options = [])
    {
        //handle options['tag']
        //$this->sanitiseNS($name); // remove any trailing dots which will break things
        $targetNS = $this->determineTargetNS($name);
        if ($this->container->has($targetNS)) {
            $this->container->append($targetNS, $value);
        } else {
            $this->container->set($targetNS, $value);
            $this->addPopulatedNamespace($targetNS);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return Collector
     * @throws StatisticsCollectorException
     */
    protected function updateValueAtNamespace($name, $value, $options = [])
    {
        $targetNS = $this->determineTargetNS($name);
        if ($this->container->has($targetNS)) {
            $this->container->set($targetNS, $value);
        } else {
            throw new StatisticsCollectorException("Unable to update value at " . $this->getCurrentNamespace() . static::SEPARATOR . $name);
        }
        return $this;
    }

    /**
     * @param $name
     * @return Collector
     */
    protected function removeValueFromNamespace($name)
    {
        $targetNS = $this->determineTargetNS($name);
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
        $targetNS = $this->determineTargetNS($name);
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
     * Currently PHP only allows numbers and strings to be incremented.
     *
     * @param mixed $value
     * @return bool
     */
    protected function is_incrementable($value)
    {
        return (is_int($value) || is_string($value));
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
        }
        return true;
    }

    /**
     * Check that a namespace element exists
     * @param $name
     * @return bool
     * @throws StatisticsCollectorException
     */
    protected function checkExists($name)
    {
        $targetNS = $this->determineTargetNS($name);
        if (!$this->container->has($targetNS)) {
            throw new StatisticsCollectorException("The namespace does not exist: " . $targetNS);
        }
        return true;
    }

    protected function calculateStatsSum($stats)
    {
        if ($this->is_summable($stats)) {
            switch (gettype($stats)) {
                case "string":
                case "integer":
                    return $stats;
                case "array":
                    return $this->sum($stats);
            }
        } else {
            throw new StatisticsCollectorException("Unable to return sum for this type of value: " . gettype($stats));
        }

    }

    protected function calculateStatsAverage($stats)
    {
        if ($this->is_averageable($stats)) {
            switch (gettype($stats)) {
                case "string":
                case "integer":
                    return $stats;
                case "array":
                    return $this->average($stats);
            }
        } else {
            throw new StatisticsCollectorException("Unable to return average for this type of value: " . gettype($stats));
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
        if (in_array(gettype($value), ['integer', 'float'])) {
            return true;
        } elseif (gettype($value) === "array") {
            foreach ($value as $v) {
                if (!in_array(gettype($v), ['integer', 'float'])) {
                    return false;
                }
            }
            return true;
        } else {
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
        if (in_array(gettype($value), ['integer', 'float'])) {
            return true;
        } elseif (gettype($value) === "array") {
            foreach ($value as $v) {
                if (!in_array(gettype($v), ['integer', 'float'])) {
                    return false;
                }
            }
            return true;
        } else {
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
     * During getInstance() we want to configure the container to be an instance of Container()
     */
    protected function containerSetup()
    {
        if (!$this->container instanceof Container) {
            $this->container = new Container();
        }
    }

}