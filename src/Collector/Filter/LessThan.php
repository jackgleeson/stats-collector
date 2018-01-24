<?php

namespace Statistics\Collector\Filter;

use Statistics\Collector\AbstractCollector;

class LessThan
{

    protected $statsCollector;

    protected $value;

    /**
     * LessThan constructor.
     *
     * @param \Statistics\Collector\AbstractCollector $StatsCollector
     * @param mixed $value
     */
    public function __construct(AbstractCollector $StatsCollector, $value)
    {
    }

    public function filter()
    {
        $values = $this->statsCollector->getAll();
        foreach($values as $value) {
            //this isn't going to work due to the bug with get all root namespaces being prefixed by the dot
        }
    }

}