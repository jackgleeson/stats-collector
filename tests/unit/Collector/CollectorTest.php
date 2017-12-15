<?php

use PHPUnit\Framework\Constraint\IsType as PHPUnit_IsType;

/**
 * @covers \Statistics\Collector\Traits\SingletonInheritance
 * @covers \Statistics\Collector\Collector<extended>
 */
class CollectorTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Statistics\Collector\Collector
     */
    protected $statsCollector;

    public function setUp()
    {
        $this->statsCollector = Statistics\Collector\Collector::getInstance();
        parent::setUp();
    }

    public function testCollectorImplementsAbstractCollector()
    {
        $this->assertInstanceOf(Statistics\Collector\AbstractCollector::class, $this->statsCollector);
    }

    public function testCollectorImplementsCollectorInterface()
    {
        $this->assertInstanceOf(Statistics\Collector\iCollector::class, $this->statsCollector);
    }

    public function testCollectorImplementsSingletonInterface()
    {
        $this->assertInstanceOf(Statistics\Collector\iSingleton::class, $this->statsCollector);
    }

    public function testDefaultRootNamespaceSetInCollectorClass()
    {
        $currentNamespace = $this->statsCollector->getCurrentNamespace();

        $this->assertEquals("root", $currentNamespace);
    }

    public function testCanChangeRootNamespace()
    {
        $this->statsCollector->setNamespace("phpunit");

        $currentNamespace = $this->statsCollector->getCurrentNamespace();

        $this->assertEquals("phpunit", $currentNamespace);
    }

    public function testCanAddStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("number_of_planets", 8);

        $Stats = $this->statsCollector->getAllStats();

        $this->assertEquals(8, $Stats["test_namespace.number_of_planets"]);
    }

    /**
     * @requires PHPUnit 6
     */
    public function testCanAddIntegerAsStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("days_per_year", 365);

        $Stats = $this->statsCollector->getAllStats();

        $this->assertInternalType(PHPUnit_IsType::TYPE_INT, $Stats["test_namespace.days_per_year"]);
    }

    /**
     * @requires PHPUnit 6
     */
    public function testCanAddFloatAsStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("pi", 3.14159265359);

        $Stats = $this->statsCollector->getAllStats();

        $this->assertInternalType(PHPUnit_IsType::TYPE_FLOAT, $Stats["test_namespace.pi"]);
    }

    /**
     * @requires PHPUnit 6
     */
    public function testCanAddArrayAsStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $fibonacciSequence = [0, 1, 1, 2, 3, 5, 8, 13, 21, 34];
        $this->statsCollector->addStat("fibonacci_sequence", $fibonacciSequence);

        $Stats = $this->statsCollector->getAllStats();

        $this->assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $Stats["test_namespace.fibonacci_sequence"]);
    }

    public function testCanClobberStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("value_to_be_overwritten", 1);
        $this->statsCollector->addStat("value_to_be_overwritten", 2, $options = ['clobber' => true]);

        $Stats = $this->statsCollector->getAllStats();

        $this->assertEquals(2, $Stats["test_namespace.value_to_be_overwritten"]);
    }

    public function testCanCreateCompoundStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("compound_stat", 1);
        $this->statsCollector->addStat("compound_stat", 2);

        $Stats = $this->statsCollector->getAllStats();

        $this->assertEquals([1, 2], $Stats["test_namespace.compound_stat"]);
    }

    /**
     * @requires PHPUnit 6
     */
    public function testCanAddAssociativeArrayAsStat()
    {
        $this->statsCollector->setNamespace("test_namespace");

        $mathConstants = [
          "pi" => 3.14159265359,
          'golden_ratio' => 1.61803398875,
        ];

        $this->statsCollector->addStat("math_constants", $mathConstants);
        $Stats = $this->statsCollector->getAllStats();

        $expected = [
          "pi" => 3.14159265359,
          'golden_ratio' => 1.61803398875,
        ];

        $this->assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $Stats["test_namespace.math_constants"]);
        $this->assertEquals($expected, $Stats["test_namespace.math_constants"]);
    }

    public function testCanAddStatToNewSubNamespace()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("math.golden_ratio", 1.61803398875);
        $this->statsCollector->setNamespace("test_namespace.math");

        $currentNamespace = $this->statsCollector->getCurrentNamespace();
        $Stats = $this->statsCollector->getAllStats();

        $this->assertEquals("test_namespace.math", $currentNamespace);
        $this->assertEquals(1.61803398875, $Stats["test_namespace.math.golden_ratio"]);
    }

    public function testCanGetIndividualStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);

        $numberOfPlanets = $this->statsCollector->getStat("planets");

        $this->assertEquals(8, $numberOfPlanets);
    }

    public function testCanGetIndividualStatWithKey()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);

        $numberOfPlanets = $this->statsCollector->getStat("planets", $withKeys = true);

        $expected = [
          'test_namespace.planets' => 8,
        ];
        $this->assertEquals($expected, $numberOfPlanets);
    }

    public function testCanGetMultipleStats()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);
        $this->statsCollector->addStat("dwarf_planets", 1);

        $planetStats = $this->statsCollector->getStats([
          "planets",
          "dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }

    /**
     * This test
     */
    public function testCallToGetStatWithMultipleNamespacesReturnsMultipleStats()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);
        $this->statsCollector->addStat("dwarf_planets", 1);

        $planetStats = $this->statsCollector->getStat([
          "planets",
          "dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }


    public function testCanGetMultipleStatsWithKeys()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);
        $this->statsCollector->addStat("dwarf_planets", 1);

        $planetStats = $this->statsCollector->getStats([
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
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);

        $numberOfPlanets = $this->statsCollector->getStat(".test_namespace.planets");

        $this->assertEquals(8, $numberOfPlanets);
    }

    public function testCanGetIndividualStatWithKeyUsingAbsoluteNamespace()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);

        $numberOfPlanets = $this->statsCollector->getStat(".test_namespace.planets", $withKeys = true);

        $expected = [
          'test_namespace.planets' => 8,
        ];
        $this->assertEquals($expected, $numberOfPlanets);
    }


    public function testCanGetMultipleStatsUsingAbsoluteNamespace()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);
        $this->statsCollector->addStat("dwarf_planets", 1);

        $planetStats = $this->statsCollector->getStats([
          ".test_namespace.planets",
          ".test_namespace.dwarf_planets",
        ]);

        $expected = [8, 1];

        $this->assertEquals($expected, $planetStats);
    }


    public function testCanGetMultipleStatsWithKeysUsingAbsoluteNamespace()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);
        $this->statsCollector->addStat("dwarf_planets", 1);

        $planetStats = $this->statsCollector->getStats([
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
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path");
        $this->statsCollector->addStat("pi", 3.14159265359);

        $piStat = $this->statsCollector->getStat("this.*.pi");

        $this->assertEquals(3.14159265359, $piStat);
    }

    public function testCanGetIndividualStatUsingAbsolutePathWithWildcardOperator()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path");
        $this->statsCollector->addStat("pi", 3.14159265359);

        $piStat = $this->statsCollector->getStat(".this.is.*.pi");

        $this->assertEquals(3.14159265359, $piStat);
    }


    public function testCanGetIndividualStatWithKeyUsingWildcardOperator()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path");
        $this->statsCollector->addStat("pi", 3.14159265359);

        $piStat = $this->statsCollector->getStat("this.*.pi", $withKeys = true);

        $expected = [
          'this.is.a.really.long.namespace.path.pi' => 3.14159265359,
        ];

        $this->assertEquals($expected, $piStat);
    }

    public function testCanGetMultipleStatsUsingWildcardOperatorTargetingLeafNodes()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path.with.math.constants");
        $this->statsCollector->addStat("pi", 3.14159265359);
        $this->statsCollector->addStat("golden_ratio", 1.61803398875);

        $wildcardLeafNodes = $this->statsCollector->getStats([
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
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path.with.math.constants");
        $this->statsCollector->addStat("pi", 3.14159265359);
        $this->statsCollector->addStat("golden_ratio", 1.61803398875);

        $wildcardConstantCommonParentChildNodes = $this->statsCollector->getStat("this.*.math.constants.*");

        $expected = [
          1.61803398875,
          3.14159265359,
        ];

        $this->assertEquals($expected, $wildcardConstantCommonParentChildNodes);
    }

    public function testCanGetMultipleStatsWithKeysUsingWildcardOperatorTargetingLeafNodes()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path.with.math.constants");
        $this->statsCollector->addStat("pi", 3.14159265359);
        $this->statsCollector->addStat("golden_ratio", 1.61803398875);

        $wildcardLeafNodes = $this->statsCollector->getStats([
          "this.*.pi",
          "this.*.golden_ratio",
        ], $withKeys = true);

        $expected = [
          'this.is.a.really.long.namespace.path.with.math.constants.pi' => 3.14159265359,
          'this.is.a.really.long.namespace.path.with.math.constants.golden_ratio' => 1.61803398875,
        ];

        $this->assertEquals($expected, $wildcardLeafNodes);
    }

    public function testCanGetMultipleStatsWithKeysUsingWildcardOperatorTargetingCommonParentNode()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("this.is.a.really.long.namespace.path.with.math.constants");
        $this->statsCollector->addStat("pi", 3.14159265359);
        $this->statsCollector->addStat("golden_ratio", 1.61803398875);

        $wildcardConstantCommonParentChildNodes = $this->statsCollector->getStats([
          "this.*.math.constants.*",
        ], $withKeys = true);


        $expected = [
          'this.is.a.really.long.namespace.path.with.math.constants.golden_ratio' => 1.61803398875,
          'this.is.a.really.long.namespace.path.with.math.constants.pi' => 3.14159265359,
        ];

        $this->assertEquals($expected, $wildcardConstantCommonParentChildNodes);
    }

    public function testCanSetDefaultResultIfStatDoesNotExist()
    {
        $this->statsCollector->setNamespace("test_namespace");

        $nonExistentStat = $this->statsCollector->getStat("i_dont_exist", $withKeys = false, $default = false);

        $this->assertFalse($nonExistentStat);
    }

    public function testCanSetDefaultResultIfStatWithKeysDoesNotExist()
    {
        $this->statsCollector->setNamespace("test_namespace");

        $nonExistentStat = $this->statsCollector->getStat("i_dont_exist", $withKeys = true, $default = false);

        $expected = [
          "i_dont_exist" => false,
        ];

        $this->assertEquals($expected, $nonExistentStat);
    }

    public function testCanSetDefaultResultForMultipleResultsIfStatsDoesNotExist()
    {
        $this->statsCollector->setNamespace("test_namespace");

        $nonExistentStats = $this->statsCollector->getStats([
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
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("counter", 1);
        $this->statsCollector->incrementStat("counter");

        $counter = $this->statsCollector->getStat("counter");

        $this->assertEquals(2, $counter);
    }

    public function testCanIncrementStatWithCustomIncrementer()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("counter", 1);
        $this->statsCollector->incrementStat("counter", $increment = 2);

        $counter = $this->statsCollector->getStat("counter");

        $this->assertEquals(3, $counter);
    }

    public function testIncrementingEmptyStatCreatesNewStatAndIncrementsIt()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->incrementStat("counter");

        $counter = $this->statsCollector->getStat("counter");

        $this->assertEquals(1, $counter);
    }

    /**
     * @requires PHPUnit 5
     */
    public function testIncrementStatWhichIsNotIncrementableThrowsException()
    {
        $this->expectException(Statistics\Exception\StatisticsCollectorException::class);
        $this->expectExceptionMessage("Attempted to increment a value which cannot be incremented!");

        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("text", "dummy text");
        $this->statsCollector->incrementStat("text");
    }

    public function testCanDecrementStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("counter", 10);
        $this->statsCollector->decrementStat("counter");

        $counter = $this->statsCollector->getStat("counter");

        $this->assertEquals(9, $counter);
    }

    public function testCanDecrementStatWithCustomDecrementer()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("counter", 10);
        $this->statsCollector->decrementStat("counter", $decrement = 5);

        $counter = $this->statsCollector->getStat("counter");

        $this->assertEquals(5, $counter);
    }

    public function testDecrementingEmptyStatCreatesNewStatAndDecrementsIt()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->decrementStat("counter");

        $counter = $this->statsCollector->getStat("counter");

        $this->assertEquals(-1, $counter);
    }

    /**
     * @requires PHPUnit 5
     */
    public function testDecrementStatWhichIsNotDecrementableThrowsException()
    {
        $this->expectException(Statistics\Exception\StatisticsCollectorException::class);
        $this->expectExceptionMessage("Attempted to decrement a value which cannot be decremented!");

        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("text", "dummy text");
        $this->statsCollector->decrementStat("text");
    }

    public function testCanRemoveStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);

        $numberOfPlanets = $this->statsCollector->getStat("planets");
        $this->assertEquals(8, $numberOfPlanets);

        $this->statsCollector->removeStat('planets');
        $numberOfPlanets = $this->statsCollector->getStat("planets");
        $this->assertEquals(null, $numberOfPlanets);
    }

    /**
     * @requires PHPUnit 5
     */
    public function testRemovingStatsWithWildcardThrowsException()
    {
        $this->expectException(Statistics\Exception\StatisticsCollectorException::class);
        $this->expectExceptionMessage("Wildcard usage forbidden when removing stats (to protect you from yourself!)");

        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("planets", 8);
        $this->statsCollector->removeStat('test_namespace.*');
    }

    /**
     * @requires PHPUnit 5
     */
    public function testRemovingNonExistentStatsThrowsException()
    {
        $this->expectException(Statistics\Exception\StatisticsCollectorException::class);
        $this->expectExceptionMessage("Attempting to remove a statistic that does not exist: test_namespace.i_dont_exist");

        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->removeStat('test_namespace.i_dont_exist');
    }

    public function testCanGetCountOfIndividualStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("heights", [181, 222, 194, 143, 190]);

        $numberOfHeights = $this->statsCollector->getStatCount("heights");

        $this->assertEquals(5, $numberOfHeights);
    }

    public function testCanGetCountOfMultipleStats()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("heights", [181, 222, 194, 143, 190]);
        $this->statsCollector->addStat("weights", [200, 211, 173, 130, 187]);

        $combinedNumberOfHeightsAndWeights = $this->statsCollector->getStatsCount([
          'heights',
          'weights',
        ]);

        $this->assertEquals(10, $combinedNumberOfHeightsAndWeights);
    }

    public function testCanGetCountOfMultipleStatsUsingWildcardOperator()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("measurements.heights", [181, 222, 194, 143, 190]);
        $this->statsCollector->addStat("measurements.weights", [200, 211, 173, 130, 187]);

        $combinedNumberOfHeightsAndWeights = $this->statsCollector->getStatCount("measurements.*");

        $this->assertEquals(10, $combinedNumberOfHeightsAndWeights);
    }


    public function testCanGetAverageValuesOfIndividualStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $heights = [181, 222, 194, 143, 190];
        $this->statsCollector->addStat("heights", $heights);

        $averageHeight = $this->statsCollector->getStatAverage("heights");
        $expectedAverage = array_sum($heights) / count($heights); // 186

        $this->assertEquals($expectedAverage, $averageHeight);
    }

    public function testCanGetAverageValuesOfIndividualStatWithSingleValue()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("height", 155.5);

        $averageHeight = $this->statsCollector->getStatAverage("height");

        $this->assertEquals(155.5, $averageHeight);
    }

    public function testCanGetAverageValuesOfMultipleStats()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $gondorHeights = [181, 222, 194, 143, 190];
        $shireHeights = [96, 110, 85, 120, 111];
        $mordorHeight = 140;
        $this->statsCollector->addStat("gondor_heights", $gondorHeights);
        $this->statsCollector->addStat("shire_heights", $shireHeights);
        $this->statsCollector->addStat("mordor_height", $mordorHeight);

        $averageHeightAcrossMiddleEarth = $this->statsCollector->getStatsAverage([
          'gondor_heights',
          'shire_heights',
          'mordor_height',
        ]);

        $combinedHeights = array_merge($gondorHeights, $shireHeights, [$mordorHeight]);
        $expectedCombinedHeightsAverage = array_sum($combinedHeights) / count($combinedHeights); // 144.72727272727272

        $this->assertEquals($expectedCombinedHeightsAverage, $averageHeightAcrossMiddleEarth);
    }

    public function testCanGetAverageValuesOfMultipleStatsUsingWildcardOperator()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $gondorHeights = [181, 222, 194, 143, 190];
        $shireHeights = [96, 110, 85, 120, 111];
        $this->statsCollector->addStat("middle_earth.gondor_heights", $gondorHeights);
        $this->statsCollector->addStat("middle_earth.shire_heights", $shireHeights);

        $averageHeightAcrossGondorAndTheShire = $this->statsCollector->getStatAverage("middle_earth.*");

        $combinedHeights = array_merge($gondorHeights, $shireHeights);
        $expectedCombinedHeightsAverage = array_sum($combinedHeights) / count($combinedHeights); // 145.2

        $this->assertEquals($expectedCombinedHeightsAverage, $averageHeightAcrossGondorAndTheShire);
    }

    /**
     * @requires PHPUnit 5
     */
    public function testTryingToAverageANonNumberThrowsException()
    {
        $this->expectException(Statistics\Exception\StatisticsCollectorException::class);
        $this->expectExceptionMessage("Unable to return average for this collection of values (are they all numbers?)");

        $this->statsCollector->setNamespace("test_namespace");
        $heights = [181, 222, 194, 143, 190, "one hundred and fifty"];
        $this->statsCollector->addStat("heights", $heights);

        $this->statsCollector->getStatAverage("heights");
    }

    public function testCanGetSumOfSingleValue()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("double", 0.5);

        $double = $this->statsCollector->getStatSum("double");

        $this->assertEquals(0.5, $double);
    }

    public function testCanGetSumOfIndividualStat()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->addStat("counter", [1, 2, 3, 4, 5]);

        $counterSum = $this->statsCollector->getStatSum("counter");

        $this->assertEquals(15, $counterSum);
    }

    public function testCanGetSumOfMultipleStats()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("noahs.ark.passengers");
        $this->statsCollector->addStat("humans", 2);
        $this->statsCollector->addStat("aliens", 0);
        $this->statsCollector->addStat("animals", 99);

        $numberOfPassengers = $this->statsCollector->getStatsSum([
          "humans",
          "aliens",
          "animals",
        ]);

        $this->assertEquals(101, $numberOfPassengers);
    }

    public function testCanGetSumOfMultipleStatsUsingWildcardOperator()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("noahs.ark.passengers");
        $this->statsCollector->addStat("humans", 2);
        $this->statsCollector->addStat("aliens", 0);
        $this->statsCollector->addStat("animals", 99);

        $numberOfPassengers = $this->statsCollector->getStatSum("noahs.ark.passengers.*");

        $this->assertEquals(101, $numberOfPassengers);
    }

    /**
     * @requires PHPUnit 5
     */
    public function testTryingToSumANonNumberThrowsException()
    {
        $this->expectException(Statistics\Exception\StatisticsCollectorException::class);
        $this->expectExceptionMessage("Unable to return sum for this collection of values (are they all numbers?)");

        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("noahs.ark.passengers");
        $this->statsCollector->addStat("humans", "two");
        $this->statsCollector->addStat("aliens", 0);
        $this->statsCollector->addStat("animals", 99);

        $this->statsCollector->getStatSum("noahs.ark.passengers.*");

    }

    public function testCanGetAllAddedStats()
    {
        $this->statsCollector->setNamespace("test_namespace");
        $this->statsCollector->setNamespace("noahs.ark.passengers");
        $this->statsCollector->addStat("humans", 2);
        $this->statsCollector->addStat("aliens", 0);
        $this->statsCollector->addStat("animals", 99);

        $allStats = $this->statsCollector->getAllStats();

        //stats are returned in alphabetical order
        $expectStats = [
          'noahs.ark.passengers.aliens' => 0,
          'noahs.ark.passengers.animals' => 99,
          'noahs.ark.passengers.humans' => 2,
        ];

        $this->assertEquals($expectStats, $allStats);
    }

    /**
     * @covers \Statistics\Collector\Traits\SingletonInheritance::tearDown()
     */
    public function tearDown()
    {
        Statistics\Collector\Collector::tearDown();
        parent::tearDown();
    }

}
