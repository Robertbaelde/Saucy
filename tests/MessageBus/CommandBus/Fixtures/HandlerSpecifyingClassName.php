<?php

namespace Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures;


use Robertbaelde\Saucy\Attributes\CommandHandler;

final class HandlerSpecifyingClassName
{
    #[CommandHandler(handlingCommand: Foo::class)]
    public function handleFoo(object $foo): void
    {
    }

}
