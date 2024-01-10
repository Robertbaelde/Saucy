<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption;

use EventSauce\EventSourcing\AggregateRootId;

final readonly class AnonymousAggregateRootId implements AggregateRootId
{
    public function __construct(
        public string $aggregateRootId
    )
    {
    }

    public function toString(): string
    {
        return $this->aggregateRootId;
    }

    public static function fromString(string $aggregateRootId): static
    {
        return new static($aggregateRootId);
    }
}
