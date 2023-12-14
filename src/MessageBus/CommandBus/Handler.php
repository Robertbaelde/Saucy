<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

final readonly class Handler
{
    public function __construct(
        public string $containerIdentifier,
        public string $methodName,
    )
    {
    }
}
