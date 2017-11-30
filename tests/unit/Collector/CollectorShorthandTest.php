<?php

use PHPUnit\Framework\Constraint\IsType as PHPUnit_IsType;

class CollectorShorthandTest extends \PHPUnit\Framework\TestCase
{

    public function testCollectorImplementAbstractCollector()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();

        $this->assertInstanceOf(Statistics\Collector\AbstractCollector::class, $statsCollector);
    }

    public function testCollectorImplementsiCollectorShorthandInterface()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();

        $this->assertInstanceOf(Statistics\Collector\iCollectorShorthand::class, $statsCollector);
    }

    public function testDefaultRootNamespaceSetInCollectorClass()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();
        $currentNamespace = $statsCollector->getCurrentNamespace();

        $this->assertEquals("root", $currentNamespace);
    }

    public function testCanChangeRootNamespace()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->ns("phpunit");

        $currentNamespace = $statsCollector->getCurrentNamespace();

        $this->assertEquals("phpunit", $currentNamespace);
    }

    public function testCanAddStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("number_of_planets", 8);

        $Stats = $statsCollector->all();

        $this->assertEquals(8, $Stats["test_namespace.number_of_planets"]);
    }

    public function testCanAddIntegerAsStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("days_per_year", 365);

        $Stats = $statsCollector->all();

        $this->assertInternalType(PHPUnit_IsType::TYPE_INT, $Stats["test_namespace.days_per_year"]);
    }

    public function testCanAddFloatAsStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("pi", 3.14159265359);

        $Stats = $statsCollector->all();

        $this->assertInternalType(PHPUnit_IsType::TYPE_FLOAT, $Stats["test_namespace.pi"]);
    }

    public function testCanAddArrayAsStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $fibonacciSequence = [0, 1, 1, 2, 3, 5, 8, 13, 21, 34];
        $statsCollector->add("fibonacci_sequence", $fibonacciSequence);

        $Stats = $statsCollector->all();

        $this->assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $Stats["test_namespace.fibonacci_sequence"]);
    }

    public function testCanAddAssociativeArrayAsStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();

        $mathConstants = [
          "pi" => 5,
          'golden_ratio' => 9,
        ];

        $statsCollector->add("math_constants", $mathConstants);
        $Stats = $statsCollector->all();

        $expected = [
          "pi" => 5,
          'golden_ratio' => 9,
        ];

        $this->assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $Stats["test_namespace.math_constants"]);
        $this->assertEquals($expected, $Stats["test_namespace.math_constants"]);
    }

    public function testCanClobberStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("value_to_be_overwritten", 1);
        $statsCollector->clobber("value_to_be_overwritten", 2);

        $Stats = $statsCollector->all();

        $this->assertEquals(2, $Stats["test_namespace.value_to_be_overwritten"]);
    }


    public function testCanAddStatToNewSubNamespace()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("math.golden_ratio", 1.61803398875);
        $statsCollector->ns("test_namespace.math");

        $currentNamespace = $statsCollector->getCurrentNamespace();
        $Stats = $statsCollector->all();

        $this->assertEquals("test_namespace.math", $currentNamespace);
        $this->assertEquals(1.61803398875, $Stats["test_namespace.math.golden_ratio"]);
    }

    public function testCanGetIndividualStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);

        $numberOfPlanets = $statsCollector->get("planets");

        $this->assertEquals(8, $numberOfPlanets);
    }

    public function testCanGetIndividualStatWithKey()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);

        $numberOfPlanets = $statsCollector->getWithKey("planets");

        $expected = [
          'test_namespace.planets' => 8,
        ];
        $this->assertEquals($expected, $numberOfPlanets);
    }

    public function testCanGetMultipleStats()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);
        $statsCollector->add("dwarf_planets", 1);

        $planetStats = $statsCollector->get([
          "planets",
          "dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }


    public function testCanGetMultipleStatsWithKeys()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);
        $statsCollector->add("dwarf_planets", 1);

        $planetStats = $statsCollector->getWithKey([
          "planets",
          "dwarf_planets",
        ]);

        $expected = [
          'test_namespace.planets' => 8,
          'test_namespace.dwarf_planets' => 1,
        ];

        $this->assertEquals($expected, $planetStats);
    }

    public function testCanGetIndividualStatUsingAbsoluteNamespace()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);

        $numberOfPlanets = $statsCollector->get(".test_namespace.planets");

        $this->assertEquals(8, $numberOfPlanets);
    }

    public function testCanGetIndividualStatWithKeyUsingAbsoluteNamespace()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);

        $numberOfPlanets = $statsCollector->getWithKey(".test_namespace.planets");

        $expected = [
          'test_namespace.planets' => 8,
        ];
        $this->assertEquals($expected, $numberOfPlanets);
    }


    public function testCanGetMultipleStatsUsingAbsoluteNamespace()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);
        $statsCollector->add("dwarf_planets", 1);

        $planetStats = $statsCollector->get([
          ".test_namespace.planets",
          ".test_namespace.dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }


    public function testCanGetMultipleStatsWithKeysUsingAbsoluteNamespace()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);
        $statsCollector->add("dwarf_planets", 1);

        $planetStats = $statsCollector->getWithKey([
          ".test_namespace.planets",
          ".test_namespace.dwarf_planets",
        ]);

        $expected = [
          'test_namespace.planets' => 8,
          'test_namespace.dwarf_planets' => 1,
        ];

        $this->assertEquals($expected, $planetStats);
    }

    public function testCanGetIndividualStatUsingWildcardOperator()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("this.is.a.really.long.namespace.path");
        $statsCollector->add("pi", 3.14159265359);

        $piStat = $statsCollector->get("this.*.pi");

        $this->assertEquals(3.14159265359, $piStat);
    }


    public function testCanGetIndividualStatWithKeyUsingWildcardOperator()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("this.is.a.really.long.namespace.path");
        $statsCollector->add("pi", 3.14159265359);

        $piStat = $statsCollector->getWithKey("this.*.pi");

        $expected = [
          'this.is.a.really.long.namespace.path.pi' => 3.14159265359,
        ];

        $this->assertEquals($expected, $piStat);
    }

    public function testCanGetMultipleStatsUsingWildcardOperatorTargetingLeafNodes()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("this.is.a.really.long.namespace.path.with.math.constants");
        $statsCollector->add("pi", 3.14159265359);
        $statsCollector->add("golden_ratio", 1.61803398875);

        $wildcardLeafNodes = $statsCollector->get([
          "this.*.pi",
          "this.*.golden_ratio",
        ]);

        $expected = [
          3.14159265359,
          1.61803398875,
        ];

        $this->assertEquals($expected, $wildcardLeafNodes);
    }

    public function testCanGetMultipleStatsUsingWildcardOperatorTargetingCommonParentNode()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("this.is.a.really.long.namespace.path.with.math.constants");
        $statsCollector->add("pi", 3.14159265359);
        $statsCollector->add("golden_ratio", 1.61803398875);

        $wildcardConstantCommonParentChildNodes = $statsCollector->get([
          "this.*.math.constants.*",
        ]);

        $expected = [
          1.61803398875,
          3.14159265359,
        ];

        $this->assertEquals($expected, $wildcardConstantCommonParentChildNodes);
    }

    public function testCanGetMultipleStatsWithKeysUsingWildcardOperatorTargetingLeafNodes()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("this.is.a.really.long.namespace.path.with.math.constants");
        $statsCollector->add("pi", 3.14159265359);
        $statsCollector->add("golden_ratio", 1.61803398875);

        $wildcardLeafNodes = $statsCollector->getWithKey([
          "this.*.pi",
          "this.*.golden_ratio",
        ]);

        $expected = [
          'this.is.a.really.long.namespace.path.with.math.constants.pi' => 3.14159265359,
          'this.is.a.really.long.namespace.path.with.math.constants.golden_ratio' => 1.61803398875,
        ];

        $this->assertEquals($expected, $wildcardLeafNodes);
    }

    public function testCanGetMultipleStatsWithKeysUsingWildcardOperatorTargetingCommonParentNode()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("this.is.a.really.long.namespace.path.with.math.constants");
        $statsCollector->add("pi", 3.14159265359);
        $statsCollector->add("golden_ratio", 1.61803398875);

        $wildcardConstantCommonParentChildNodes = $statsCollector->getWithKey("this.*.math.constants.*");


        $expected = [
          'this.is.a.really.long.namespace.path.with.math.constants.golden_ratio' => 1.61803398875,
          'this.is.a.really.long.namespace.path.with.math.constants.pi' => 3.14159265359,
        ];

        $this->assertEquals($expected, $wildcardConstantCommonParentChildNodes);
    }

    public function testCanSetDefaultResultIfStatDoesNotExist()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();

        $nonExistentStat = $statsCollector->get("i_dont_exist", $withKeys = false, $default = false);

        $this->assertFalse($nonExistentStat);
    }

    public function testCanSetDefaultResultForMultipleResultsIfStatsDoesNotExist()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();

        $nonExistentStats = $statsCollector->get([
          "i_dont_exist",
          "i_dont_exist_either",
        ], $withKeys = false, $default = false);

        $expected = [
          false,
          false,
        ];

        $this->assertEquals($expected, $nonExistentStats);
    }

    public function testCanIncrementStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("counter", 1);
        $statsCollector->inc("counter");

        $counter = $statsCollector->get("counter");

        $this->assertEquals(2, $counter);
    }

    public function testCanIncrementStatWithCustomIncrementer()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("counter", 1);
        $statsCollector->inc("counter", $increment = 2);

        $counter = $statsCollector->get("counter");

        $this->assertEquals(3, $counter);
    }

    public function testCanDecrementStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("counter", 10);
        $statsCollector->dec("counter");

        $counter = $statsCollector->get("counter");

        $this->assertEquals(9, $counter);
    }

    public function testCanDecrementStatWithCustomDecrementer()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("counter", 10);
        $statsCollector->dec("counter", $decrement = 5);

        $counter = $statsCollector->get("counter");

        $this->assertEquals(5, $counter);
    }

    public function testCanRemoveStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("planets", 8);

        $numberOfPlanets = $statsCollector->get("planets");
        $this->assertEquals(8, $numberOfPlanets);

        $statsCollector->del('planets');
        $numberOfPlanets = $statsCollector->get("planets");
        $this->assertEquals(null, $numberOfPlanets);
    }

    public function testCanGetCountOfIndividualStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("heights", [181, 222, 194, 143, 190]);

        $numberOfHeights = $statsCollector->count("heights");

        $this->assertEquals(5, $numberOfHeights);
    }

    public function testCanGetCountOfMultipleStats()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("heights", [181, 222, 194, 143, 190]);
        $statsCollector->add("weights", [200, 211, 173, 130, 187]);

        $combinedNumberOfHeightsAndWeights = $statsCollector->count([
          'heights',
          'weights',
        ]);

        $this->assertEquals(10, $combinedNumberOfHeightsAndWeights);
    }

    public function testCanGetCountOfMultipleStatsUsingWildcardOperator()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("measurements.heights", [181, 222, 194, 143, 190]);
        $statsCollector->add("measurements.weights", [200, 211, 173, 130, 187]);

        $combinedNumberOfHeightsAndWeights = $statsCollector->count("measurements.*");

        $this->assertEquals(10, $combinedNumberOfHeightsAndWeights);
    }


    public function testCanGetAverageValuesOfIndividualStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $heights = [181, 222, 194, 143, 190];
        $statsCollector->add("heights", $heights);

        $averageHeight = $statsCollector->avg("heights");
        $expectedAverage = array_sum($heights) / count($heights); // 186

        $this->assertEquals($expectedAverage, $averageHeight);
    }

    public function testCanGetAverageValuesOfMultipleStats()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $gondorHeights = [181, 222, 194, 143, 190];
        $shireHeights = [96, 110, 85, 120, 111];
        $statsCollector->add("gondor_heights", $gondorHeights);
        $statsCollector->add("shire_heights", $shireHeights);

        $averageHeightAcrossGondorAndTheShire = $statsCollector->avg([
          'gondor_heights',
          'shire_heights',
        ]);

        $combinedHeights = array_merge($gondorHeights, $shireHeights);
        $expectedCombinedHeightsAverage = array_sum($combinedHeights) / count($combinedHeights); // 145.2

        $this->assertEquals($expectedCombinedHeightsAverage, $averageHeightAcrossGondorAndTheShire);
    }

    public function testCanGetAverageValuesOfMultipleStatsUsingWildcardOperator()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $gondorHeights = [181, 222, 194, 143, 190];
        $shireHeights = [96, 110, 85, 120, 111];
        $statsCollector->add("middle_earth.gondor_heights", $gondorHeights);
        $statsCollector->add("middle_earth.shire_heights", $shireHeights);

        $averageHeightAcrossGondorAndTheShire = $statsCollector->avg("middle_earth.*");

        $combinedHeights = array_merge($gondorHeights, $shireHeights);
        $expectedCombinedHeightsAverage = array_sum($combinedHeights) / count($combinedHeights); // 145.2

        $this->assertEquals($expectedCombinedHeightsAverage, $averageHeightAcrossGondorAndTheShire);
    }

    public function testCanGetSumOfIndividualStat()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->add("counter", [1, 2, 3, 4, 5]);

        $counterSum = $statsCollector->sum("counter");

        $this->assertEquals(15, $counterSum);
    }

    public function testCanGetSumOfMultipleStats()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("noahs.ark.passengers");
        $statsCollector->add("humans", 2);
        $statsCollector->add("aliens", 0);
        $statsCollector->add("animals", 99);

        $numberOfPassengers = $statsCollector->sum([
          "humans",
          "aliens",
          "animals",
        ]);

        $this->assertEquals(101, $numberOfPassengers);
    }

    public function testCanGetSumOfMultipleStatsUsingWildcardOperator()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("noahs.ark.passengers");
        $statsCollector->add("humans", 2);
        $statsCollector->add("aliens", 0);
        $statsCollector->add("animals", 99);

        $numberOfPassengers = $statsCollector->sum("noahs.ark.passengers.*");

        $this->assertEquals(101, $numberOfPassengers);
    }


    public function testCanGetAllAddedStats()
    {
        $statsCollector = $this->getTestStatsCollectorInstance();
        $statsCollector->ns("noahs.ark.passengers");
        $statsCollector->add("humans", 2);
        $statsCollector->add("aliens", 0);
        $statsCollector->add("animals", 99);

        $allStats = $statsCollector->all();

        //stats are returned in alphabetical order
        $expectStats = [
          'noahs.ark.passengers.aliens' => 0,
          'noahs.ark.passengers.animals' => 99,
          'noahs.ark.passengers.humans' => 2,
        ];

        $this->assertEquals($expectStats, $allStats);
    }

    public function tearDown()
    {
        Statistics\Collector\Collector::tearDown(true);
        parent::tearDown();
    }

    private function getTestStatsCollectorInstance()
    {
        $statsCollector = Statistics\Collector\Collector::getInstance();
        $statsCollector->ns("test_namespace");
        return $statsCollector;
    }

}