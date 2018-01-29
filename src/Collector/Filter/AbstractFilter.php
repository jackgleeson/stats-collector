<?php

namespace Statistics\Collector\Filter;

use Statistics\Helper\TypeHelper;

abstract class AbstractFilter implements iFilter
{

    /**
     * @var array
     */
    protected $originalStats;

    /**
     * @var mixed
     */
    protected $filterParams;

    /**
     * @var array of results after applying filter
     */
    protected $results;

    /**
     * LessThan constructor.
     *
     * @param array $stats stats to be filtered
     * @param $filterParams
     */
    public function __construct(array $stats, ...$filterParams)
    {
        $this->originalStats = $stats;
        $this->filterParams = $filterParams;
    }

    /**
     * @return array
     */
    public function filter()
    {
        $typeHelper = new TypeHelper();
        foreach ($this->originalStats as $namespace => $value) {
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
            if ($this->condition($value) === false) {
                unset($compoundValues[$key]);
            }
        }
        if (count($compoundValues) > 0) {
            $this->results[$namespace] = $compoundValues;
        }
    }

    /**
     * Perform filter condition check
     *
     * @param mixed $value
     *
     * @return mixed
     */
    abstract protected function condition($value);

}