<?php

namespace Robertbaelde\Saucy\Tests;

use PHPUnit\Framework\TestCase;
use Robertbaelde\Saucy\ClassNameMapper\ClassResolver;

final class ClassResolverTest extends TestCase
{
    /** @test */
    public function it_can_get_classes(): void
    {
        $classResolver = new ClassResolver();
        $result = $classResolver->getClasses('Robertbaelde\Saucy\Tests\stubs', '/Users/robertbaelde/code/Saucy/tests');
        $this->assertContains('Robertbaelde\Saucy\Tests\stubs\TestEvent', $result);
    }
}
