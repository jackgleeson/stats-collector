<?php

use PHPUnit\Framework\Constraint\IsType as PHPUnit_IsType;

class CollectorTest extends \PHPUnit\Framework\TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testCollectorImplementAbstractCollector()
    {
        $StatsCollector = Statistics\Collector\Collector::getInstance();

        $this->assertInstanceOf(Statistics\Collector\AbstractCollector::class, $StatsCollector);
    }

    public function testCollectorImplementsiCollectorInterface()
    {
        $StatsCollector = Statistics\Collector\Collector::getInstance();

        $this->assertInstanceOf(Statistics\Collector\iCollector::class, $StatsCollector);
    }

    public function testCollectorImplementsiCollectorShorthandInterface()
    {
        $StatsCollector = Statistics\Collector\Collector::getInstance();

        $this->assertInstanceOf(Statistics\Collector\iCollectorShorthand::class, $StatsCollector);
    }

    public function testDefaultRootNamespaceSetInCollectorClass()
    {
        $StatsCollector = Statistics\Collector\Collector::getInstance();
        $currentNamespace = $StatsCollector->getCurrentNamespace();

        $this->assertEquals("root", $currentNamespace);
    }

    public function testCanChangeRootNamespace()
    {
        $StatsCollector = Statistics\Collector\Collector::getInstance();
        $StatsCollector->setNamespace("phpunit");

        $currentNamespace = $StatsCollector->getCurrentNamespace();

        $this->assertEquals("phpunit", $currentNamespace);
    }

    public function testCanAddStat()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("number_of_planets", 8);

        $Stats = $StatsCollector->getAllStats();

        $this->assertEquals(8, $Stats["test_namespace.number_of_planets"]);
    }

    public function testCanAddIntegerAsStat()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("days_per_year", 365);

        $Stats = $StatsCollector->getAllStats();

        $this->assertInternalType(PHPUnit_IsType::TYPE_INT, $Stats["test_namespace.days_per_year"]);
    }

    public function testCanAddFloatAsStat()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("pi", 3.14159265359);

        $Stats = $StatsCollector->getAllStats();

        $this->assertInternalType(PHPUnit_IsType::TYPE_FLOAT, $Stats["test_namespace.pi"]);
    }

    public function testCanAddArrayAsStat()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $fibonacciSequence = [0, 1, 1, 2, 3, 5, 8, 13, 21, 34];
        $StatsCollector->addStat("fibonacci_sequence", $fibonacciSequence);

        $Stats = $StatsCollector->getAllStats();

        $this->assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $Stats["test_namespace.fibonacci_sequence"]);
    }


    public function testCanAddStatToNewSubNamespace()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("math.golden_ratio", 1.61803398875);
        $StatsCollector->setNamespace("test_namespace.math");

        $currentNamespace = $StatsCollector->getCurrentNamespace();
        $Stats = $StatsCollector->getAllStats();

        $this->assertEquals("test_namespace.math", $currentNamespace);
        $this->assertEquals(1.61803398875, $Stats["test_namespace.math.golden_ratio"]);
    }

    public function testCanGetIndividualStat()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);

        $numberOfPlanets = $StatsCollector->getStat("planets");

        $this->assertEquals(8, $numberOfPlanets);
    }

    public function testCanGetIndividualStatWithKey()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);

        $numberOfPlanets = $StatsCollector->getStat("planets", $withKeys = true);

        $expected = [
          'test_namespace.planets' => 8,
        ];
        $this->assertEquals($expected, $numberOfPlanets);
    }

    public function testCanGetMultipleStats()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);
        $StatsCollector->addStat("dwarf_planets", 1);

        $planetStats = $StatsCollector->getStats([
          "planets",
          "dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }


    public function testCanGetMultipleStatsWithKeys()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);
        $StatsCollector->addStat("dwarf_planets", 1);

        $planetStats = $StatsCollector->getStats([
          "planets",
          "dwarf_planets",
        ], $withKeys = true);

        $expected = [
          'test_namespace.planets' => 8,
          'test_namespace.dwarf_planets' => 1,
        ];

        $this->assertEquals($expected, $planetStats);
    }

    public function testCanGetIndividualStatUsingAbsoluteNamespace()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);

        $numberOfPlanets = $StatsCollector->getStat(".test_namespace.planets");

        $this->assertEquals(8, $numberOfPlanets);
    }

    public function testCanGetIndividualStatWithKeyUsingAbsoluteNamespace()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);

        $numberOfPlanets = $StatsCollector->getStat(".test_namespace.planets", $withKeys = true);

        $expected = [
          'test_namespace.planets' => 8,
        ];
        $this->assertEquals($expected, $numberOfPlanets);
    }


    public function testCanGetMultipleStatsUsingAbsoluteNamespace()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);
        $StatsCollector->addStat("dwarf_planets", 1);

        $planetStats = $StatsCollector->getStats([
          ".test_namespace.planets",
          ".test_namespace.dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }


    public function testCanGetMultipleStatsWithKeysUsingAbsoluteNamespace()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->addStat("planets", 8);
        $StatsCollector->addStat("dwarf_planets", 1);

        $planetStats = $StatsCollector->getStats([
          ".test_namespace.planets",
          ".test_namespace.dwarf_planets",
        ], $withKeys = true);

        $expected = [
          'test_namespace.planets' => 8,
          'test_namespace.dwarf_planets' => 1,
        ];

        $this->assertEquals($expected, $planetStats);
    }

    public function testCanGetIndividualStatUsingWildcardOperator()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->setNamespace("this.is.a.really.long.namespace.path");
        $StatsCollector->addStat("pi", 3.14159265359);

        $piStat = $StatsCollector->getStat("this.*.pi");

        $this->assertEquals(3.14159265359, $piStat);
    }


    public function testCanGetIndividualStatWithKeyUsingWildcardOperator()
    {
        $StatsCollector = $this->getTestStatsCollectorInstance();
        $StatsCollector->setNamespace("this.is.a.really.long.namespace.path");
        $StatsCollector->addStat("pi", 3.14159265359);

        $piStat = $StatsCollector->getStat("this.*.pi", $withKeys = true);

        $expected = [
          'this.is.a.really.long.namespace.path.pi' => 3.14159265359,
        ];

        $this->assertEquals($expected, $piStat);
    }

    public function tearDown()
    {
        Statistics\Collector\Collector::tearDown(true);
        parent::tearDown();
    }

    private function getTestStatsCollectorInstance()
    {
        $StatsCollector = Statistics\Collector\Collector::getInstance();
        $StatsCollector->setNamespace("test_namespace");
        return $StatsCollector;
    }

}
