<?php

namespace Robertbaelde\Saucy\Tests;

use PHPUnit\Framework\TestCase;
use Robertbaelde\Saucy\ClassNameMapper\AttributeClassNameInflector;
use Robertbaelde\Saucy\Tests\stubs\TestEvent;

final class AttributeClassNameInflectorTest extends TestCase
{
    /** @test */
    public function it_can_parse_event_with_name_attribute()
    {
        $inflector = AttributeClassNameInflector::create( [TestEvent::class]);
        $this->assertEquals('test_event', $inflector->instanceToType(new TestEvent()));
    }

    /** @test */
    public function it_can_get_classname_from_name()
    {
        $inflector = AttributeClassNameInflector::create( [TestEvent::class]);
        $this->assertEquals(TestEvent::class, $inflector->typeToClassName('test_event'));
    }
}
