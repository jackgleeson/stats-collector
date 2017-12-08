<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers \Statistics\Collector\Traits\SingletonInheritance
 */
class SingletonInheritanceTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Statistics\Collector\Collector
     */
    protected $statsCollector;

    public function testCanTearDownAllSingletonInstances()
    {
        //open up access to $Statistics\Collector\Collector::instances[]
        $reflectionProperty = new \ReflectionProperty( Statistics\Collector\Collector::class, "instances");
        $reflectionProperty->setAccessible(true);

        $statsCollector = Statistics\Collector\Collector::getInstance();
        $this->assertNotEmpty($reflectionProperty->getValue($statsCollector));

        $statsCollector::tearDown(true);
        $this->assertEmpty($reflectionProperty->getValue($statsCollector));
    }


}