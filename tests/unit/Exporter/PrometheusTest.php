<?php


class PrometheusTest extends \PHPUnit\Framework\TestCase
{

    protected $statsFilename;

    protected $statsFilePath;

    protected $statsFileExtension;

    public function testPrometheusExporterImplementsiExporterInterface()
    {
        $prometheusExporter = new Statistics\Exporter\Prometheus();

        $this->assertInstanceOf(Statistics\Exporter\iExporter::class, $prometheusExporter);
    }

    public function testExportStatsToPrometheusFile()
    {
        $tmpPath = $this->createTmpDir() . "/";
        $tmpPrometheusFilename = "test_stats";
        $prometheusExtension = Statistics\Exporter\Prometheus::$extension;

        // confirm file doesn't exist before export
        $this->assertFileNotExists($tmpPath . $tmpPrometheusFilename . $prometheusExtension);

        $statsCollector = $this->getTestStatsCollectorInstance();
        $prometheusExporter = new Statistics\Exporter\Prometheus("test_stats");
        $prometheusExporter->path = $tmpPath;
        $prometheusExporter->export($statsCollector);

        // confirm file now exists after export
        $this->assertFileExists($tmpPath . $tmpPrometheusFilename . $prometheusExtension);

        //clean up
        $this->removeTmpFile($tmpPath . $tmpPrometheusFilename . $prometheusExtension);
        $this->removeTmpDir($tmpPath);
    }

    private function getTestStatsCollectorInstance()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->setNamespace("test_namespace");
        $statsCollector->addStat("test", 1);
        return $statsCollector;
    }

    private function createTmpDir()
    {
        $tempfile = tempnam(sys_get_temp_dir(), 'tmp_');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        if (is_dir($tempfile)) {
            return $tempfile;
        }
    }

    private function removeTmpDir($tmpDir)
    {
        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }
    }

    private function removeTmpFile($tmpFile)
    {
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }


}
