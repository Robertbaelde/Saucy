<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

use EventSauce\EventSourcing\AggregateRootId;

final readonly class AggregateRootStreamNameInflector
{
    public function __construct(
        private string $delimiter = '##',
    )
    {
    }

    public function getStreamFor(string $aggregateRootType, AggregateRootId $aggregateRootId): AggregateStream
    {
        return new AggregateStream(aggregateRootType: $aggregateRootType, aggregateRootId: $aggregateRootId->toString(), delimiter: $this->delimiter);
    }
}
