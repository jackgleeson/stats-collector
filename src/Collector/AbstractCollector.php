<?php


namespace Statistics\Collector;

use Dflydev\DotAccessData\Data as Container;
use Statistics\Collector\Helper\ArrayHelper;
use Statistics\Collector\Helper\MathHelper;
use Statistics\Collector\Helper\TypeHelper;
use Statistics\Collector\Traits\CollectorShorthand;
use Statistics\Collector\Traits\SingletonInheritance;
use Statistics\Exception\StatisticsCollectorException;

abstract class AbstractCollector implements iCollector, iCollectorShorthand
{

    use CollectorShorthand, SingletonInheritance;

    /**
     * Namespace separator
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
     * Container for stats data
     *
     * @var Container
     */
    protected $container;

    /**
     * Array of populated leaf node paths
     *
     * @var array
     */
    protected $populatedNamespaces = [];

    /**
     * Record a statistic for a subject
     *
     *
     * @param string $name name of statistic to be added to namespace
     * @param mixed $value
     * @param array $options
     *
     * @return \Statistics\Collector\AbstractCollector
     */
    public function addStat($name, $value, $options = [])
    {
        $options = array_merge($this->getDefaultAddValueToNamespaceOptions(), $options);
        $this->addValueToNamespace($name, $value, $options);
        return $this;
    }

    /**
     * Remove a statistic
     *
     * @param string $namespace
     *
     * @return \Statistics\Collector\AbstractCollector
     * @throws StatisticsCollectorException
     */
    public function removeStat($namespace)
    {
        if ($this->isWildcardNamespace($namespace)) {
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
     *
     * @param string $namespace
     * @param int|float $increment
     *
     * @return \Statistics\Collector\AbstractCollector
     * @throws StatisticsCollectorException
     */
    public function incrementStat($namespace, $increment = 1)
    {
        if ($this->checkExists($namespace) !== true) {
            $this->addStat($namespace, 0);
        }

        $currentValue = $this->getStat($namespace);

        if ($this->isIncrementable($currentValue)) {
            $options['clobber'] = true;
            $this->addStat($namespace, $currentValue + $increment, $options);
            return $this;
        } else {
            throw new StatisticsCollectorException("Attempted to increment a value which cannot be incremented! (" . $namespace . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Decrement a statistic
     *
     * @param $namespace
     * @param int|float $decrement
     *
     * @return \Statistics\Collector\AbstractCollector
     * @throws StatisticsCollectorException
     */
    public function decrementStat($namespace, $decrement = -1)
    {
        if ($this->checkExists($namespace) !== true) {
            $this->addStat($namespace, 0);
        }

        $currentValue = $this->getStat($namespace);

        if ($this->isDecrementable($currentValue)) {
            $options['clobber'] = true;
            $this->addStat($namespace, $currentValue - abs($decrement), $options);
            return $this;
        } else {
            throw new StatisticsCollectorException("Attempted to decrement a value which cannot be decremented! (" . $namespace . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Retrieve the statistic value for a given namespace.
     *
     * Wildcard searches and arrays of namespace targets will be forwarded to getStats()
     *
     * @param mixed $namespace
     * @param bool $withKeys
     * @param mixed $default default value to be returned if stat $namespace is empty
     *
     * @see \Statistics\Collector\AbstractCollector::getStats()
     * @return mixed
     */
    public function getStat($namespace, $withKeys = false, $default = null)
    {
        if (is_array($namespace)) {
            return $this->getStats($namespace, $withKeys, $default);
        }

        if ($this->isWildcardNamespace($namespace)) {
            return $this->getStats([$namespace], $withKeys, $default);
        }

        if ($this->checkExists($namespace) === true) {
            if ($withKeys === true) {
                $resolvedNamespace = $this->getTargetNamespaces($namespace);
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
     * Retrieve a collection of statistics for an array of given namespaces
     *
     * @param array $namespaces
     * @param bool $withKeys
     * @param mixed $default default value to be returned if stat $namespace is empty
     *
     * @return mixed
     */
    public function getStats(array $namespaces, $withKeys = false, $default = null)
    {
        $resolvedNamespaces = $this->getTargetNamespaces($namespaces, true);
        if (!is_array($resolvedNamespaces)) {
            $resolvedNamespaces = [$resolvedNamespaces];
        }

        $stats = [];
        foreach ($resolvedNamespaces as $namespace) {
            $stat = $this->getStat($namespace, $withKeys, $default);
            $stats = array_merge($stats, (is_array($stat) ? $stat : [$stat]));
        }

        if (count($stats) === 1 && ($withKeys == false)) {
            return $stats[0];
        } else {
            return $stats;
        }
    }

    /**
     * Count the number of values recorded for a given stat
     *
     * @param $namespace
     *
     * @return int
     */
    public function getStatCount($namespace)
    {
        $value = $this->getStat($namespace);
        return (new MathHelper)->count($value);
    }

    /**
     * Count the number of values recorded for a collection of given stats
     *
     * @param array $namespaces
     *
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
     *
     * @return float|int
     * @throws \Statistics\Exception\StatisticsCollectorException
     */
    public function getStatAverage($namespace)
    {
        $value = $this->getStat($namespace);
        return $this->calculateStatsAverage($value);
    }

    /**
     * @param array $namespaces
     *
     * @return float|int
     * @throws \Statistics\Exception\StatisticsCollectorException
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
     *
     * @return float|int
     * @throws \Statistics\Exception\StatisticsCollectorException
     */
    public function getStatSum($namespace)
    {
        $value = $this->getStat($namespace);
        return $this->calculateStatsSum($value);
    }

    /**
     * @param array $namespaces
     *
     * @return float|int
     * @throws \Statistics\Exception\StatisticsCollectorException
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
     *  Retrieve all recorded statistics.
     *
     * @return array $stats array of stats with full namespace as key
     */
    public function getAllStats()
    {
        $stats = [];
        foreach ($this->populatedNamespaces as $namespace) {
            $stats[$namespace] = $this->getStatsContainer()->get($namespace);
        }
        return $stats;
    }

    /**
     * @param $namespace
     *
     * @return \Statistics\Collector\AbstractCollector
     */
    public function setNamespace($namespace)
    {
        return $this->setCurrentNamespace($namespace);

    }

    /**
     * Return the current namespace. Default to default namespace if none set.
     *
     * @return string
     */
    public function getCurrentNamespace()
    {
        return ($this->namespace === null) ? $this->getDefaultNamespace() : $this->namespace;
    }

    /**
     * Set the default root namespace for statistics to be stored within is a custom namespace is not set.
     *
     * @return string
     */
    abstract protected function getDefaultNamespace();

    /**
     * Get the default $options values to be used in conjunction with addValueToNamespace()
     *
     * Defaults:
     * - flatten=true (reduce multi-dimensional arrays to a single array)
     * - clobber=false (assigning values to an existing stat results in the value being appended to and does not
     * overwrite).
     *
     * @see \Statistics\Collector\AbstractCollector::addValueToNamespace()
     * @return array
     */
    protected function getDefaultAddValueToNamespaceOptions()
    {
        $options = [
          'flatten' => true,
          'clobber' => false,
        ];
        return $options;
    }

    /**
     * Determine whether a namespace contains a wildcard operator
     *
     * @param $namespace
     *
     * @return bool
     */
    protected function isWildcardNamespace($namespace)
    {
        return (strpos($namespace, static::WILDCARD) !== false);
    }

    /**
     * Determine whether a namespace contains an absolute path indicated by the fist character being a
     * separator '.' value
     *
     * @param $namespace
     *
     * @return bool
     */
    protected function isAbsolutePathNamespace($namespace)
    {
        return (strpos($namespace, static::SEPARATOR) === 0);
    }

    /**
     * Return an array of populated leaf node paths
     *
     * @return array
     */
    protected function getPopulatedNamespaces()
    {
        return $this->populatedNamespaces;
    }

    /**
     * TODO: validate namespace argument
     *
     * @param $namespace
     *
     * @return \Statistics\Collector\AbstractCollector
     */
    protected function setCurrentNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string $namespace
     *
     * @return mixed
     */
    protected function resolveWildcardNamespace($namespace)
    {
        // clear absolute path initial '.' as not needed for wildcard
        if ($this->isAbsolutePathNamespace($namespace)) {
            $namespace = $target = substr($namespace, 1);
        }

        // add a additional namespace route by prepending the current parent ns to the wildcard query
        // handle relative and absolute wildcard searching
        $additionalNamespace = $this->getCurrentNamespace() . "." . $namespace;

        $expandedPaths = [];
        foreach ($this->getPopulatedNamespaces() as $populatedNamespace) {
            if (fnmatch($namespace, $populatedNamespace) || fnmatch($additionalNamespace, $populatedNamespace)) {
                // we convert the expanded wildcard paths to absolute paths by prepending '.'
                // this prevents the getTargetNamespaces() from treating the namespace as a sub namespace
                $expandedPaths[] = static::SEPARATOR . $populatedNamespace;
            }
        }

        return $expandedPaths;
    }

    /**
     * Determine the target namespace(s) based on the namespace value(s)
     * '.' present at beginning indicates absolute namespace path
     * '.' present but not at the beginning indicates branch namespace path of
     * the current namespace
     * '.' not present indicates leaf-node namespace of current namespace
     * '*' present indicates wildcard namespace path expansion required
     *
     * @param mixed $namespaces
     * @param bool $returnAbsolute
     *
     * @return mixed $resolvedNamespaces
     */
    protected function getTargetNamespaces($namespaces, $returnAbsolute = false)
    {
        $resolvedNamespaces = [];
        if (!is_array($namespaces)) {
            $namespaces = [$namespaces];
        }

        foreach ($namespaces as $namespace) {
            switch (true) {
                case $this->isWildcardNamespace($namespace):
                    $expandedWildcardPaths = $this->resolveWildcardNamespace($namespace);
                    $resolvedNamespaces = array_merge($resolvedNamespaces, $expandedWildcardPaths);
                    break;
                case $this->isAbsolutePathNamespace($namespace):
                    $resolvedNamespaces[] = ($returnAbsolute === false) ? substr($namespace, 1) : $namespace;
                    break;
                default:
                    // leaf-node of current namespace e.g. 'dates' or sub-namespace e.g 'sub.path.of.current.namespace'
                    $expandedRelativeNodeNamespace = $this->getCurrentNamespace() . static::SEPARATOR . $namespace;
                    $resolvedNamespaces[] = ($returnAbsolute === false) ? $expandedRelativeNodeNamespace :
                      static::SEPARATOR . $expandedRelativeNodeNamespace;
            }
        }

        if (count($resolvedNamespaces) === 1) {
            return $resolvedNamespaces[0];
        } else {
            return array_unique($resolvedNamespaces);
        }
    }

    /**
     * Add value(s) to a namespace.
     *
     * If the namespace exists, the value will either be appended to or overwritten depending on $options['clobber']
     * If the namespace is new, the value will be stored at the target namespace
     *
     * If $options['flatten'] is set to true, multi-dimensional arrays will be flattened to one array.
     *
     * @param string $namespace
     * @param mixed $value
     * @param array $options
     *
     * @return \Statistics\Collector\AbstractCollector
     */
    protected function addValueToNamespace($namespace, $value, $options)
    {
        $flatten = $options['flatten'];
        $clobber = $options['clobber'];
        $targetNS = $this->getTargetNamespaces($namespace);

        if ($this->getStatsContainer()->has($targetNS) && ($clobber === false)) {
            $this->addValueToExistingNamespace($targetNS, $value, $flatten);
        } elseif ($this->getStatsContainer()->has($targetNS) && ($clobber === true)) {
            $this->overwriteExistingNamespace($targetNS, $value, $flatten);
        } else {
            $this->addValueToNewNamespace($targetNS, $value, $flatten);
            $this->addPopulatedNamespace($targetNS);
        }
        return $this;
    }

    /**
     * @param $name
     *
     * @return \Statistics\Collector\AbstractCollector
     */
    protected function removeValueFromNamespace($name)
    {
        $targetNS = $this->getTargetNamespaces($name);
        $this->getStatsContainer()->remove($targetNS);
        $this->removePopulatedNamespace($targetNS);
        return $this;
    }

    /**
     * Retrieve stats value from container, return null if not found.
     *
     * @param $name
     *
     * @return mixed
     */
    protected function getValueFromNamespace($name)
    {
        $targetNS = $this->getTargetNamespaces($name);
        return $this->getStatsContainer()->get($targetNS);
    }

    /**
     * Check to see if value can be incremented.
     * Currently PHP allows numbers and strings to be incremented. We only want numbers
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isIncrementable($value)
    {
        return (new TypeHelper())->isIntOrFloat($value);
    }

    /**
     * Check to see if value can be decremented.
     * Currently PHP allows numbers and strings to be incremented. We only want numbers
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isDecrementable($value)
    {
        return (new TypeHelper())->isIntOrFloat($value);
    }

    /**
     * Keep track of populated namespaces
     *
     * @param $namespace
     *
     * @return bool
     */
    protected function addPopulatedNamespace($namespace)
    {
        array_push($this->populatedNamespaces, $namespace);
        $this->sortPopulatedNamespaces();
        return true;
    }

    /**
     * Remove a namespace from the populated namespaces array (typically when
     * it becomes empty)
     *
     * @param $namespace
     *
     * @return bool
     */
    protected function removePopulatedNamespace($namespace)
    {
        unset($this->populatedNamespaces[$namespace]);
        $this->sortPopulatedNamespaces();
        return true;
    }

    /**
     * Check that a namespace element exist
     *
     * @param string $namespace
     *
     * @return bool
     */
    protected function checkExists($namespace)
    {
        $resolvedNamespace = $this->getTargetNamespaces($namespace);
        return $this->getStatsContainer()->has($resolvedNamespace);
    }

    /**
     * @param  mixed $stats
     *
     * @return float|int
     * @throws \Statistics\Exception\StatisticsCollectorException
     */
    protected function calculateStatsSum($stats)
    {
        $mathHelper = new MathHelper();
        if ($mathHelper->isSummable($stats)) {
            switch (gettype($stats)) {
                case "integer":
                case "double":
                    return $stats;
                case "array":
                    return $mathHelper->sum($stats);
            }
        } else {
            throw new StatisticsCollectorException("Unable to return sum for this collection of values (are they all numbers?)");
        }
    }

    /**
     * @param mixed $stats
     *
     * @return float|int
     * @throws \Statistics\Exception\StatisticsCollectorException
     */
    protected function calculateStatsAverage($stats)
    {
        $mathHelper = new MathHelper();
        if ($mathHelper->isAverageable($stats)) {
            switch (gettype($stats)) {
                case "integer":
                case "double":
                    return $stats;
                case "array":
                    return $mathHelper->average($stats);
            }
        } else {
            throw new StatisticsCollectorException("Unable to return average for this collection of values (are they all numbers?)");
        }
    }

    /**
     * Sort populated namespaces first by namespace level and then alphabetical order
     *
     * @return bool
     */
    protected function sortPopulatedNamespaces()
    {
        usort($this->populatedNamespaces, function ($a, $b) {
            //sort on namespace nesting level
            $namespaceLevel = strnatcmp(substr_count($a, '.'), substr_count($b, '.'));
            if ($namespaceLevel === 0) {
                // if nesting level is equal (0), sort on alphabetical order using "natural order" algorithm
                return strnatcmp($a, $b);
            } else {
                return $namespaceLevel;
            }
        });
        return true;
    }

    /**
     * Get current stats container.
     *
     * Set $this->container to be an instance of \Dflydev\DotAccessData\Data (aliased as Container) if being
     * retrieved for the first time.
     *
     * @see Container
     */
    private function getStatsContainer()
    {
        if (!$this->container instanceof Container) {
            $this->container = new Container();
        }
        return $this->container;
    }

    /**
     * See AbstractCollector::addValueToNamespace() for documentation
     *
     * @see AbstractCollector::addValueToNamespace()
     *
     * @param $namespace
     * @param $value
     * @param $flatten
     */
    private function addValueToExistingNamespace($namespace, $value, $flatten)
    {
        if ($flatten === true && is_array($value)) {
            $flattenedValue = (new ArrayHelper())->flatten($value);
            $current = $this->getStatsContainer()->get($namespace);
            $new = (is_array($current)) ? array_merge($current, $flattenedValue) : array_merge([$current],
              $flattenedValue);
            $this->getStatsContainer()->set($namespace, $new);
        } else {
            $this->getStatsContainer()->append($namespace, $value);
        }
    }

    /**
     * See AbstractCollector::addValueToNamespace() for documentation
     *
     * @see AbstractCollector::addValueToNamespace()
     *
     * @param $namespace
     * @param $value
     * @param $flatten
     */
    private function overwriteExistingNamespace($namespace, $value, $flatten)
    {
        // overwrite behaviour is identical to addValueToNewNamespace() so call is forwarded on.
        $this->addValueToNewNamespace($namespace, $value, $flatten);
    }

    /**
     * See AbstractCollector::addValueToNamespace() for documentation
     *
     * @see AbstractCollector::addValueToNamespace()
     *
     * @param $namespace
     * @param $value
     * @param $flatten
     */
    private function addValueToNewNamespace($namespace, $value, $flatten)
    {
        if ($flatten === true && is_array($value)) {
            $flattenedValue = (new ArrayHelper())->flatten($value);
            $this->getStatsContainer()->set($namespace, $flattenedValue);
        } else {
            $this->getStatsContainer()->set($namespace, $value);
        }
    }

}