<?php


class PrometheusTest extends \PHPUnit\Framework\TestCase
{

    protected $promFilename;

    protected $promFilePath;

    protected $promFileExtension;

    public function testPrometheusExporterImplementsiExporterInterface()
    {
        $prometheusExporter = new Statistics\Exporter\Prometheus();

        $this->assertInstanceOf(Statistics\Exporter\iExporter::class, $prometheusExporter);
    }

    public function testExportCreatesPrometheusFile()
    {
        $this->setupTmpStatsFileProperties();
        $promFileLocation = $this->promFilePath . DIRECTORY_SEPARATOR . $this->promFilename . $this->promFileExtension;

        // confirm file doesn't exist before export
        $this->assertFileNotExists($promFileLocation);

        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->addStat("test", 1);

        $prometheusExporter = new Statistics\Exporter\Prometheus($this->promFilename, $this->promFilePath);
        $prometheusExporter->export($statsCollector);

        // confirm file now exists after export
        $this->assertFileExists($promFileLocation);

        //clean up
        $this->removeTmpFile($promFileLocation);
        $this->removeTmpDir($this->promFilePath);
    }

    public function testExportMapsNamespaceDotsToUnderscores()
    {
        $this->setupTmpStatsFileProperties();
        $promFileLocation = $this->promFilePath . DIRECTORY_SEPARATOR . $this->promFilename . $this->promFileExtension;

        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->setNamespace("this.is.a.really.long.namespace");
        $statsCollector->addStat("pi", 3.14159265359);

        $prometheusExporter = new Statistics\Exporter\Prometheus($this->promFilename, $this->promFilePath);
        $prometheusExporter->export($statsCollector);

        $statsAssocArray = $this->buildArrayFromPrometheusOutputFile($promFileLocation);
        $expectedStatName = 'this_is_a_really_long_namespace_pi';

        $this->assertArrayHasKey($expectedStatName, $statsAssocArray);

        //clean up
        $this->removeTmpFile($promFileLocation);
        $this->removeTmpDir($this->promFilePath);
    }

    public function testExportOutputsValidStats()
    {
        $this->setupTmpStatsFileProperties();
        $promFileLocation = $this->promFilePath . DIRECTORY_SEPARATOR . $this->promFilename . $this->promFileExtension;

        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->setNamespace("milky_way");
        $statsCollector->addStat("planets", 100000000000);
        $statsCollector->addStat("stars", 400000000000);
        $statsCollector->addStat("age_in_years", 13800000000);

        $prometheusExporter = new Statistics\Exporter\Prometheus($this->promFilename, $this->promFilePath);
        $prometheusExporter->export($statsCollector);

        $statsAssocArray = $this->buildArrayFromPrometheusOutputFile($promFileLocation);

        $expectedStats = [
          'milky_way_planets' => 100000000000,
          'milky_way_stars' => 400000000000,
          'milky_way_age_in_years' => 13800000000,
        ];

        $this->assertEquals($expectedStats, $statsAssocArray);

        //clean up
        $this->removeTmpFile($promFileLocation);
        $this->removeTmpDir($this->promFilePath);
    }


    public function tearDown()
    {
        Statistics\Collector\Collector::tearDown(true);
    }

    private function getTestStatsCollectorInstance()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->setNamespace("test_namespace");
        return $statsCollector;
    }

    private function buildArrayFromPrometheusOutputFile($prometheusFileLocation)
    {
        $statsWrittenAssocArray = [];
        if (file_exists($prometheusFileLocation)) {
            $statsFileFullPath = $prometheusFileLocation;
            $statsWritten = rtrim(file_get_contents($statsFileFullPath)); // remove trailing \n
            $statsWrittenLinesArray = explode("\n", $statsWritten);
            foreach ($statsWrittenLinesArray as $statsLine) {
                list($name, $value) = explode(' ', $statsLine);
                $statsWrittenAssocArray[$name] = $value;
            }
        } else {
            return "Prometheus file does not exist";
        }

        return $statsWrittenAssocArray;
    }

    private function setupTmpStatsFileProperties($filename = "test_stats")
    {
        $this->promFilename = $filename;
        $this->promFilePath = $this->createTmpDir();
        $this->promFileExtension = Statistics\Exporter\Prometheus::$extension;
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
