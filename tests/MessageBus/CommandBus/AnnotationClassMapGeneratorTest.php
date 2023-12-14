<?php

namespace Robertbaelde\Saucy\Tests\MessageBus\CommandBus;

use Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures\Foo;
use Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures\FooCommandHandler;
use PHPUnit\Framework\TestCase;
use Robertbaelde\Saucy\MessageBus\CommandBus\AnnotationClassMapGenerator;
use Robertbaelde\Saucy\MessageBus\CommandBus\Handler;
use Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures\HandlerSpecifyingClassName;

final class AnnotationClassMapGeneratorTest extends TestCase
{
    /** @test */
    public function it_generates_a_map_based_on_command_handler_annotations()
    {
        $generator = new AnnotationClassMapGenerator([
            FooCommandHandler::class,
        ]);
        $map = $generator->getMap();

        $this->assertEquals([
            Foo::class => new Handler(FooCommandHandler::class, 'handleFoo'),
        ], $map);
    }

    /** @test */
    public function when_annotation_specifies_command_name_it_registers_that_command()
    {
        $generator = new AnnotationClassMapGenerator([
            HandlerSpecifyingClassName::class,
        ]);
        $map = $generator->getMap();

        $this->assertEquals([
            Foo::class => new Handler(HandlerSpecifyingClassName::class, 'handleFoo'),
        ], $map);
    }
}
