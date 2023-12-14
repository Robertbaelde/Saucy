<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

use EventSauce\EventSourcing\AggregateRootId;
use Robertbaelde\Saucy\EventSourcing\AggregateRoot;
use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\StreamShouldNotExists;
use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\WithLastKnownEventId;

final class EventSauceRepository
{
    public function __construct(
        private EventStore $eventStore,
        private AggregateRootStreamNameInflector $aggregateRootStreamNameInflector,
    )
    {
    }

    /**
     * @template T of AggregateRoot
     * @param class-string<T> $aggregateRootClass
     * @return T
     */
    public function retrieve(string $aggregateRootClass, AggregateRootId $aggregateRootId): object
    {
        $events = $this->eventStore->getEvents(
            stream: $this->aggregateRootStreamNameInflector->getStreamFor($aggregateRootClass, $aggregateRootId)
        );
        return $aggregateRootClass::reconstituteFromEvents(
            aggregateRootId: $aggregateRootId,
            events: $events->events(),
            checkpoint: $events->checkpoint
        );
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $this->eventStore->appendToStream(
            stream: $this->aggregateRootStreamNameInflector->getStreamFor($aggregateRoot::class, $aggregateRoot->aggregateRootId()),
            envelopes: Envelopes::wrapEvents($aggregateRoot->releaseEvents()),
            concurrencyCheck: $aggregateRoot->lastCheckpoint() ? new WithLastKnownEventId($aggregateRoot->lastCheckpoint()) : new StreamShouldNotExists());
    }
}
