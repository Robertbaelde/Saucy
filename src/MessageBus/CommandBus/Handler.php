<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

final readonly class Handler
{
    public function __construct(
        public string $containerIdentifier,
        public string $methodName,
        public bool $isStatic = false,
        public ?string $aggregateRootIdProperty = null,
        public ?string $aggregateRootIdCommandMethod = null,
        public bool $queue = false,
    )
    {
    }
}
