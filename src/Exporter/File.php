<?php

namespace Statistics\Exporter;

use Statistics\Collector\iCollector;

/**
 * Write out stats to a file
 */
class File implements iExporter
{

    /**
     * File extension
     *
     * @var string
     */
    public static $extension = '.stats';

    /**
     * Directory where we should write file
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
     * Separator character between namespace and stat value
     *
     * @var string
     */
    public $separator = "=";

    /**
     * @param string $path
     * @param string $filename
     */
    public function __construct($filename = "output", $path = ".")
    {
        $this->filename = $filename;
        $this->path = $path;
    }

    /**
     * Transform array of statistical data into output data and write to file.
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
        $this->writeStatisticsToFile($output);
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
        foreach ($statistics as $namespace => $stats) {
            if (is_array($stats)) {
                foreach ($stats as $key => $stat) {
                    $contents[] = $this->mapStatToLine($namespace, $stat);
                }
            } else {
                $contents[] = $this->mapStatToLine($namespace, $stats);
            }
        }

        //convert array to output string
        $strOutput = implode('', $contents);
        return $strOutput;
    }

    /**
     * Write output file
     *
     * @param $output
     */
    protected function writeStatisticsToFile($output)
    {
        $outputPath = $this->path . DIRECTORY_SEPARATOR . $this->filename . self::$extension;
        file_put_contents($outputPath, $output);
    }

    /**
     * @param string $namespace
     * @param mixed $stat
     *
     * @return string
     */
    protected function mapStatToLine($namespace, $stat)
    {
        return $namespace . $this->separator . $stat . PHP_EOL;
    }
}