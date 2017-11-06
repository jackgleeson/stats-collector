<?php

namespace Statistics\Exporter;

/**
 * Write out metrics in a Prometheus-readable format.
 * Each component gets its own file, and each call to
 * reportMetrics overwrites the file with the new data.
 */
class Prometheus implements iExporter
{
    /**
     * Prometheus files file extension
     * @var string
     */
    public static $extension = '.prom';

    /**
     * Directory where we should write Prometheus files
     *
     * @var string $path
     */
    protected $path;

    /**
     * @param string $path
     */
    public function __construct($path = '.')
    {
        $this->path = $path;
    }

    public function export(array $data)
    {
        $this->writeDataToFiles($data);
    }

    protected function writeDataToFiles(array $data)
    {
        foreach ($data as $subject => $stats) {
            $subject = $this->mapDotsToUnderscore($subject);

            if (is_array($stats)) {
                foreach ($stats as $stat) {
                    $contents[] = "$subject $stat\n";
                }
            } else {
                $contents[] = "$subject $stats\n";
            }
        }

        // prometheus for now?
        file_put_contents($this->path . "prometheus" . self::$extension, implode('', $contents));
    }

    private function mapDotsToUnderscore($input)
    {
        return str_replace(".", "_", $input);
    }


}