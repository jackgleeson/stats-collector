<?php


namespace Statistics;

use Statistics\Exporter\ExporterInterface;
use Dflydev\DotAccessData\Data as Container;

/**
 * Statistics Collector
 *
 * This object is intended to serve as storage for application-wide statistics
 * captured during the lifecycle of a request. Recorded statistics can then be exported
 * via a backend specific exporter class to file, db, other.
 *
 * Reportable subjects are defined as custom namespaces. The identifier namespace is
 * entirely up to the user e.g. queue.donations.received or civi.user.unsubscribed
 *
 * TODO:
 * - implement exporter strategy object to hanldle backend specific export/output logic (Prometheus being the first)
 * - move to Smashpig project?
 * - add updateStat behaviour
 * - add $additionalOptions to addStat method custom backend specific tags
 * - add targetNS option to all CRUD stat methods (easy ones complete)
 * - consider naming 'setNamespace' to 'useNamespace'
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
     * @param null $targetNamespace
     * @param array $additionalOptions
     * @return $this
     */
    public function addStat($name, $value, $targetNamespace = null, $additionalOptions = [])
    {
        if ($targetNamespace !== null) {
            $originalNamespace = $this->getCurrentNamespace();
            $this->setNamespace($targetNamespace);
            $this->addValueToNamespace($name, $value, $additionalOptions);
            $this->setNamespace($originalNamespace);
        } else {
            $this->addValueToNamespace($name, $value, $additionalOptions);
        }
        return $this;
    }

    /**
     * Delete a statistic
     * @param $name
     * @param null $targetNamespace
     * @return $this
     */
    public function removeStat($name, $targetNamespace = null)
    {
        if ($targetNamespace !== null) {
            $originalNamespace = $this->getCurrentNamespace();
            $this->setNamespace($targetNamespace);
            $this->removeValueFromNamespace($name);
            $this->setNamespace($originalNamespace);
        } else {
            $this->removeValueFromNamespace($name);
        }
        return $this;
    }

    /**
     * Increment a statistic
     *
     * @param string $name name of statistic to be added to namespace
     * @param int $increment
     * @return $this
     * @throws \Exception
     */
    public function incrementStat($name, $increment = 1)
    {
        $this->checkExists($name);
        $currentValue = $this->getValueFromNamespace($name);
        if ($this->is_incrementable($currentValue)) {
            $this->updateValueAtNamespace($name, $currentValue + $increment);
            return $this;
        } else {
            throw new \Exception("Attemped to increment a value which cannot be incremented! (" . $name . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Decrement a statistic
     *
     * @param string $name name of statistic to be added to namespace
     * @param int $decrement
     * @return $this
     * @throws \Exception
     */
    public function decrementStat($name, $decrement = -1)
    {
        $this->checkExists($name);
        $currentValue = $this->getValueFromNamespace($name);
        if ($this->is_incrementable($currentValue)) {
            $this->updateValueAtNamespace($name, $currentValue + $decrement);
            return $this;
        } else {
            throw new \Exception("Attemped to decrement a value which cannot be decremented! (" . $name . ":" . gettype($currentValue) . ")");
        }
    }


    /**
     * Retrieve statistic for a given subject namespace
     * @param $name name of statistic to be added to namespace
     * @param string $targetNamespace
     * @return mixed
     */
    public function getStat($name, $targetNamespace = null)
    {
        if ($targetNamespace !== null) {
            $originalNamespace = $this->getCurrentNamespace();
            $this->setNamespace($targetNamespace);
            $this->getValueFromNamespace($name);
            $this->setNamespace($originalNamespace);
        } else {
            $this->getValueFromNamespace($name);
        }
    }

    /**
     * TODO:
     * - confirm array values are incrementable and throw if not
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getStatAverage($name)
    {
        $this->checkExists($name);
        $value = $this->getValueFromNamespace($name);
        if ($this->is_averageable($value)) {
            switch (gettype($value)) {
                case "string":
                case "integer":
                    return $value;
                case "array":
                    $total = 0;
                    foreach ($value as $stat) {
                        $total += $stat;
                    }
                    return $total / count($value);
            }
        } else {
            throw new \Exception("Unable to return average for this type of value: " . gettype($value));
        }
    }

    /**
     *  Retrieve statistics for all subject namespaces
     *
     * TODO:
     * - take array of namespaces with wildcards to target specific namespaces
     * @param string $namespace
     * @return array
     * @throws \Exception
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
            throw new \Exception("Not currently implemented!");
        }
    }

    /**
     * Export statistics using exporter object
     *
     * TODO:
     * - take array of namespaces to target specific namespaces
     *
     * @param string $namespaces
     * @param ExporterInterface $Exporter
     * @return
     * @throws \Exception
     */
    public function export($namespaces = "*", ExporterInterface $Exporter)
    {
        if ($namespaces === "*") {
            return $Exporter->export($this->getAllStats());
        } else {
            throw new \Exception("Not currently implemented!");

        }
    }

    /**
     * Iterate through stats array to find namespace container and set value
     * @param string $namespace
     * @param mixed $value
     * @return bool
     */


    /**
     * TODO:
     * - validate namespace argument
     * @param $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }


    /**
     * @return array
     */
    public function getPopulatedNamespaces()
    {
        return $this->populatedNamespaces;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return bool
     */
    protected function addValueToNamespace($name, $value, $options = [])
    {
        if ($this->container->has($this->getCurrentNamespace() . static::SEPARATOR . $name)) {
            $this->container->append($this->getCurrentNamespace() . static::SEPARATOR . $name, $value);
        } else {
            $this->container->set($this->getCurrentNamespace() . static::SEPARATOR . $name, $value);
            $this->addPopulatedNamespace($this->getCurrentNamespace() . static::SEPARATOR . $name);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return $this
     * @throws \Exception
     */
    protected function updateValueAtNamespace($name, $value, $options = [])
    {
        if ($this->container->has($this->getCurrentNamespace() . static::SEPARATOR . $name)) {
            $this->container->set($this->getCurrentNamespace() . static::SEPARATOR . $name, $value);
        } else {
            throw new \Exception("Unable to update value at " . $this->getCurrentNamespace() . static::SEPARATOR . $name);
        }
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    protected function removeValueFromNamespace($name)
    {
        $this->container->remove($this->getCurrentNamespace() . static::SEPARATOR . $name);
        $this->removePopulatedNamespace($this->getCurrentNamespace() . static::SEPARATOR . $name);
        return $this;
    }

    /**
     * Retrieve stats value from container, return null if not found.
     * @param $name
     * @return mixed
     */
    protected function getValueFromNamespace($name)
    {
        return $this->container->get($this->getCurrentNamespace() . static::SEPARATOR . $name, null);
    }

    /**
     * Return the current namespace. Default to default namespace if none set.
     * @return string
     */
    protected function getCurrentNamespace()
    {
        return ($this->namespace === null) ? $this->getDefaultNamespace() : $this->namespace;
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
     * @throws \Exception
     */
    protected function checkExists($name)
    {
        if (!$this->container->has($this->getCurrentNamespace() . static::SEPARATOR . $name)) {
            throw new \Exception("The namespace does not exist: " . $this->getCurrentNamespace() . static::SEPARATOR . $name);
        }
        return true;
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