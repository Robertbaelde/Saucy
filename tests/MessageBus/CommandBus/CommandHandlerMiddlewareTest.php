<?php

namespace Robertbaelde\Saucy\Tests\MessageBus\CommandBus;

use Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures\Foo;
use Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures\FooCommandHandler;
use PHPUnit\Framework\TestCase;
use Robertbaelde\Saucy\MessageBus\CommandBus\MappedCommandHandlerLocator;
use Robertbaelde\Saucy\MessageBus\CommandBus\CommandHandlerMiddleware;
use Robertbaelde\Saucy\MessageBus\CommandBus\Handler;
use Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures\InMemoryContainer;

final class CommandHandlerMiddlewareTest extends TestCase
{
    /** @test */
    public function it_can_find_a_handler_and_call_it(): void
    {
        $fooCommandHandler = new FooCommandHandler();

        $commandHandler = new CommandHandlerMiddleware(
            container: new InMemoryContainer([
                FooCommandHandler::class => $fooCommandHandler,
            ]),
            handlerLocator: new MappedCommandHandlerLocator([
                Foo::class => new Handler(FooCommandHandler::class, 'handleFoo'),
            ]),
        );

        $commandHandler->run(new Foo(), fn() => null);
        $this->assertTrue($fooCommandHandler->wasCalledOnce());
    }
}
