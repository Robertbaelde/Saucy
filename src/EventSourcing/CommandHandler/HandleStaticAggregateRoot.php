<?php

namespace Robertbaelde\Saucy\EventSourcing\CommandHandler;

final readonly class HandleStaticAggregateRoot
{
    public function __construct(
        public string $aggregateRootClass,
        public string $method,
        public object $command
    )
    {
    }
}
