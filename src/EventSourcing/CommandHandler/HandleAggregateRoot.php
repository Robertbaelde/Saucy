<?php

namespace Robertbaelde\Saucy\EventSourcing\CommandHandler;

use EventSauce\EventSourcing\AggregateRootId;

final readonly class HandleAggregateRoot
{
    public function __construct(
        public AggregateRootId $aggregateRootId,
        public string $aggregateRootClass,
        public string $method,
        public object $command
    )
    {
    }
}
