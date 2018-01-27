<?php

namespace Statistics\Collector\Filter;

use Statistics\Collector\AbstractCollector;
use Statistics\Helper\TypeHelper;

abstract class AbstractFilter implements iFilter
{

    /**
     * @var AbstractCollector
     */
    protected $collector;

    /**
     * @var mixed
     */
    protected $filterValue;

    /**
     * @var array of results after applying filter
     */
    protected $results;

    /**
     * LessThan constructor.
     *
     * @param \Statistics\Collector\AbstractCollector $StatsCollector
     * @param string $filterValue value to filter stat values against
     */
    public function __construct(AbstractCollector $StatsCollector, $filterValue)
    {
        $this->collector = $StatsCollector;
        $this->filterValue = $filterValue;
    }

    /**
     * @return array
     */
    public function filter()
    {
        $typeHelper = new TypeHelper();
        foreach ($this->collector->getAllStats() as $namespace => $value) {
            if ($typeHelper->isCompoundStat($value)) {
                $this->filterCompoundStat($namespace, $value);
            } else {
                $this->filterScalarStat($namespace, $value);
            }
        }
        return $this->results;
    }

    protected function filterScalarStat($namespace, $value)
    {
        if ($this->condition($value) === true) {
            $this->results[$namespace] = $value;
        }
    }

    protected function filterCompoundStat($namespace, $value)
    {
        $compoundValues = $value;
        foreach ($compoundValues as $key => $value) {
            if ($this->condition($value) === true) {
                unset($compoundValues[$key]);
            }
        }
        $this->results[$namespace] = $compoundValues;
    }

    /**
     * Perform filter condition check
     *
     * @param $value
     *
     * @return mixed
     */
    abstract protected function condition($value);

}