<?php


namespace Statistics\Collector;

/**
 * Class ShorthandCollector
 *
 * Shorthand/Alias class to simplify implementation of stats collector
 *
 * @package Statistics\Collector
 */
class ShorthandCollector extends AbstractCollector
{

    /**
     * Alias method for getting stats
     *
     * @see AbstractCollector::getStat()
     * @see AbstractCollector::getStats()
     *
     * @param $namespace
     * @param bool $withKeys
     * @param null $default
     *
     * @return array|mixed
     */
    public function get($namespace, $withKeys = false, $default = null)
    {
        if (is_array($namespace)) {
            return $this->getStats($namespace, $withKeys, $default);
        } else {
            return $this->getStat($namespace, $withKeys, $default);
        }
    }

    /**
     * Alias method for getting stats with keys
     *
     * @see AbstractCollector::getStat()
     * @see AbstractCollector::getStats()
     *
     * @param $namespace
     * @param null $default
     *
     * @return array|mixed
     */
    public function getWithKey($namespace, $default = null)
    {
        if (is_array($namespace)) {
            return $this->getStats($namespace, true, $default);
        } else {
            return $this->getStat($namespace, true, $default);
        }
    }

    /**
     * Alias method for adding stats
     *
     * @see AbstractCollector::addStat()
     *
     * @param $name
     * @param $value
     * @param array $options
     *
     * @return \Statistics\Collector\iCollector
     */
    public function add($name, $value, $options = [])
    {
        return $this->addStat($name, $value, $options);
    }

    /**
     * Alias method for overwriting stats
     *
     * @see AbstractCollector::addStat()
     *
     * @param $name
     * @param $value
     * @param array $options
     *
     * @return \Statistics\Collector\iCollector
     */
    public function clobber($name, $value, $options = [])
    {
        $options['clobber'] = true;
        return $this->addStat($name, $value, $options);
    }

    /**
     * Alias method for removing stats
     *
     * @see AbstractCollector::removeStat()
     *
     * @param $namespace
     *
     * @return \Statistics\Collector\iCollector
     */
    public function del($namespace)
    {
        return $this->removeStat($namespace);
    }

    /**
     * Alias method for incrementing stats
     *
     * @see AbstractCollector::incrementStat()
     *
     * @param $namespace
     * @param int|float $increment
     *
     * @return \Statistics\Collector\iCollector
     */
    public function inc($namespace, $increment = 1)
    {
        return $this->incrementStat($namespace, $increment);
    }

    /**
     * Alias method for decrementing stats
     *
     * @see AbstractCollector::decrementStat()
     *
     * @param $namespace
     * @param int|float $decrement
     *
     * @return \Statistics\Collector\iCollector
     */
    public function dec($namespace, $decrement = -1)
    {
        return $this->decrementStat($namespace, $decrement);
    }

    /**
     * Alias method for averaging stats
     *
     * @see AbstractCollector::getStatsAverage()
     * @see AbstractCollector::getStatAverage()
     *
     * @param $namespace
     *
     * @return float|int
     */
    public function avg($namespace)
    {
        if (is_array($namespace)) {
            return $this->getStatsAverage($namespace);
        } else {
            return $this->getStatAverage($namespace);
        }
    }

    /**
     * Alias method for getting the sum of stats
     *
     * @see AbstractCollector::getStatsSum()
     * @see AbstractCollector::getStatSum()
     *
     * @param array $namespace
     *
     * @return float|int
     */
    public function sum($namespace)
    {
        if (is_array($namespace)) {
            return $this->getStatsSum($namespace);
        } else {
            return $this->getStatSum($namespace);
        }
    }

    /**
     * Alias method for counting the number of stats for a given namespace
     *
     * @see AbstractCollector::getStatsCount()
     * @see AbstractCollector::getStatCount()
     *
     * @param $namespace
     *
     * @return int
     */
    public function count($namespace)
    {
        if (is_array($namespace)) {
            return $this->getStatsCount($namespace);
        } else {
            return $this->getStatCount($namespace);
        }
    }

    /**
     * Alias method for returning all stats
     *
     * @see AbstractCollector::getAllStats()
     * @return array
     */
    public function all()
    {
        return $this->getAllStats();
    }

    /**
     * Alias method for setting the current namespace
     *
     * @see AbstractCollector::setNamespace()
     *
     * @param $namespace
     *
     * @return \Statistics\Collector\iCollector
     */
    public function ns($namespace)
    {
        return $this->setNamespace($namespace);
    }


}