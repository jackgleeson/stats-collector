<?php

namespace Statistics\Exporter;

use Statistics\Collector\iCollector;

interface iExporter
{
    public function export($data);

}