<?php


class FileTest extends \PHPUnit\Framework\TestCase
{

    protected $filename;

    protected $filePath;

    protected $fileExtension;

    public function testExporterImplementsiExporterInterface()
    {
        $exporter = new Statistics\Exporter\Prometheus();

        $this->assertInstanceOf(Statistics\Exporter\iExporter::class, $exporter);
    }

    public function testExportCreatesFile()
    {
        $this->setupTmpStatsFileProperties();
        $fileLocation = $this->filePath . DIRECTORY_SEPARATOR . $this->filename . $this->fileExtension;

        // confirm file doesn't exist before export
        $this->assertFileNotExists($fileLocation);

        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->addStat("test", 1);

        $exporter = new Statistics\Exporter\File($this->filename, $this->filePath);
        $exporter->export($statsCollector);

        // confirm file now exists after export
        $this->assertFileExists($fileLocation);

        //clean up
        $this->removeTmpFile($fileLocation);
        $this->removeTmpDir($this->filePath);
    }

    public function testExportOutputsValidStats()
    {
        $this->setupTmpStatsFileProperties();
        $fileLocation = $this->filePath . DIRECTORY_SEPARATOR . $this->filename . $this->fileExtension;

        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->setNamespace("milky_way");
        $statsCollector->addStat("planets", 100000000000);
        $statsCollector->addStat("stars", 400000000000);
        $statsCollector->addStat("age_in_years", 13800000000);

        $exporter = new Statistics\Exporter\File($this->filename, $this->filePath);
        $exporter->export($statsCollector);

        $statsAssocArray = $this->buildArrayFromOutputFile($fileLocation);

        $expectedStats = [
          'milky_way.planets' => 100000000000,
          'milky_way.stars' => 400000000000,
          'milky_way.age_in_years' => 13800000000,
        ];

        $this->assertEquals($expectedStats, $statsAssocArray);

        //clean up
        $this->removeTmpFile($fileLocation);
        $this->removeTmpDir($this->filePath);
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

    private function buildArrayFromOutputFile($fileLocation)
    {
        $statsWrittenAssocArray = [];
        if (file_exists($fileLocation)) {
            $statsFileFullPath = $fileLocation;
            $statsWritten = rtrim(file_get_contents($statsFileFullPath)); // remove trailing \n
            $statsWrittenLinesArray = explode("\n", $statsWritten);
            foreach ($statsWrittenLinesArray as $statsLine) {
                list($name, $value) = explode('=', $statsLine);
                $statsWrittenAssocArray[$name] = $value;
            }
        } else {
            return "File does not exist";
        }

        return $statsWrittenAssocArray;
    }

    private function setupTmpStatsFileProperties($filename = "test_stats")
    {
        $this->filename = $filename;
        $this->filePath = $this->createTmpDir();
        $this->fileExtension = ".stats";
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
