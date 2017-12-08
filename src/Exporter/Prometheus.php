<?php

namespace Statistics\Exporter;

use Statistics\Collector\iCollector;

/**
 * Write out metrics in a Prometheus-readable format.
 */
class Prometheus implements iExporter
{

    /**
     * Prometheus files extension
     *
     * @var string
     */
    public static $extension = '.prom';

    /**
     * Directory where we should write Prometheus files
     *
     * @var string $path
     */
    public $path;

    /**
     * Filename to write statistics out to
     *
     * @var string
     */
    public $filename;

    /**
     * @param string $path
     * @param string $filename
     */
    public function __construct($filename = "prometheus", $path = ".")
    {
        $this->filename = $filename;
        $this->path = $path;
    }


    /**
     * Transform array of statistical data into Prometheus metrics output and
     * write to file.
     *
     * Take either an array of key=>value statistical data or an instance of
     * iCollector.
     *
     * @param array|iCollector $statistics
     *
     * @return bool
     */
    public function export($statistics)
    {
        if ($statistics instanceof iCollector) {
            $statistics = $statistics->getAllStats();
        }
        $output = $this->mapStatisticsToOutput($statistics);
        $this->writeStatisticsToPrometheusFile($output);
        return true;
    }

    /**
     * @param $statistics
     *
     * @return string
     */
    protected function mapStatisticsToOutput($statistics)
    {
        $contents = [];
        foreach ($statistics as $subject => $stats) {
            $subject = $this->mapDotsToUnderscore($subject);
            if (is_array($stats)) {
                foreach ($stats as $key => $stat) {
                    if ($this->isMetricWithLabelAsKey($key)) {
                        $contents[] = $this->mapToMetricLabelLineOutput($subject, $key, $stat);
                    } else {
                        $contents[] = $this->mapToMetricLineOutput($subject, $stat);
                    }
                }
            } else {
                $contents[] = $this->mapToMetricLineOutput($subject, $stats);
            }
        }

        //convert array to string output
        $strOutput = implode('', $contents);
        return $strOutput;
    }

    /**
     * Write output file
     *
     * @param $output
     */
    protected function writeStatisticsToPrometheusFile($output)
    {
        $outputPath = $this->path . DIRECTORY_SEPARATOR . $this->filename . self::$extension;
        file_put_contents($outputPath, $output);
    }

    /**
     * Check to see if a we should create a metric label based on the contents of the statistic array key
     *
     * TODO:
     * This crude check at the moment simply detects whether or not the key is non-numeric. If it's non-numeric
     * we treat it as a label. This could be vastly improved by adding a metric label key convention such as
     * "label:<label-name>" do move away form the current default label type of "filter"
     *
     * @param $key
     *
     * @return bool
     */
    private function isMetricWithLabelAsKey($key)
    {
        return (!is_numeric($key)) ? true : false;
    }

    /**
     * Map non-numeric strings to a default label called "filter"
     *
     * TODO: work out how to control label names and potentially add more than more label
     *
     * @param string $subject
     * @param string $key
     * @param mixed $stat
     *
     * @return string
     */
    private function mapToMetricLabelLineOutput($subject, $key, $stat)
    {
        return $subject . "{filter=\"" . $key . "\"} " . $stat . PHP_EOL;
    }

    /**
     * @param string $subject
     * @param mixed $stat
     *
     * @return string
     */
    private function mapToMetricLineOutput($subject, $stat)
    {
        return $subject . " " . $stat . PHP_EOL;
    }

    /**
     * @param $input
     *
     * @return mixed
     */
    private function mapDotsToUnderscore($input)
    {
        return str_replace(".", "_", $input);
    }


}