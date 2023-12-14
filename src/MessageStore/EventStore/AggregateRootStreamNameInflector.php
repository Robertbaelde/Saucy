<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

use EventSauce\EventSourcing\AggregateRootId;
use Robertbaelde\Saucy\MessageStore\Stream;

final readonly class AggregateRootStreamNameInflector
{
    public function __construct(
        private string $delimiter = '##',
    )
    {
    }

    public function getStreamFor(string $aggregateRootType, AggregateRootId $aggregateRootId): Stream
    {
        return new Stream($aggregateRootType . $this->delimiter . $aggregateRootId->toString());
    }
}
