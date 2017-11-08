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
     * @var string
     */
    public $filename;

    /**
     * @param string $path
     * @param string $filename
     */
    public function __construct($filename = "prometheus", $path = "./")
    {
        $this->filename = $filename;
        $this->path = $path;
    }


    /**
     * @param iCollector $Statistics
     * @return bool
     */
    public function export(iCollector $Statistics)
    {
        $this->writeStatisticsToPrometheusFile($Statistics);
        return true;
    }

    /**
     * @param iCollector $Statistics
     */
    protected function writeStatisticsToPrometheusFile($Statistics)
    {
        foreach ($Statistics->getAllStats() as $subject => $stats) {
            $subject = $this->mapDotsToUnderscore($subject);

            if (is_array($stats)) {
                foreach ($stats as $stat) {
                    $contents[] = "$subject $stat\n";
                }
            } else {
                $contents[] = "$subject $stats\n";
            }
        }

        file_put_contents($this->path . $this->filename . self::$extension, implode('', $contents));
    }

    private function mapDotsToUnderscore($input)
    {
        return str_replace(".", "_", $input);
    }


}