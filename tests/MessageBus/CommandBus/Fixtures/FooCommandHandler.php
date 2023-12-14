<?php

namespace Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures;


use Robertbaelde\Saucy\Attributes\CommandHandler;

final class FooCommandHandler
{
    private array $callStack = [];

    public function __construct()
    {
    }

    #[CommandHandler]
    public function handleFoo(Foo $foo): void
    {
        $this->callStack[] = $foo;
    }

    public function wasCalledOnce(): bool
    {
        return count($this->callStack) === 1;
    }
}
