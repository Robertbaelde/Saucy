<?php

namespace Robertbaelde\Saucy\EventSourcing;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use Robertbaelde\Saucy\EventSourcing\EventStore\AggregateRootStreamNameInflector;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\StreamShouldNotExists;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\WithLastKnownEventId;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\WithLastKnownSequenceNumber;
use Robertbaelde\Saucy\EventSourcing\EventStore\Event;
use Robertbaelde\Saucy\EventSourcing\EventStore\Events;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStoreHeader;
use Robertbaelde\Saucy\EventSourcing\EventStore\Headers;
use Robertbaelde\Saucy\MessageStore\EventStore\EventIdGenerator;

final class EventSauceRepository
{
    public function __construct(
        private EventStore $eventStore,
        private AggregateRootStreamNameInflector $aggregateRootStreamNameInflector,
        private EventIdGenerator $eventIdGenerator,
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
        return $aggregateRootClass::reconstituteFromEvents(
            aggregateRootId: $aggregateRootId,
            events: $this->eventStore->getEvents(
                stream: $this->aggregateRootStreamNameInflector->getStreamFor($aggregateRootClass, $aggregateRootId)
            )->asGeneratorForEventSauce(),
        );
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $events = $aggregateRoot->releaseEvents();
        if (count($events) === 0) {
            return;
        }

        $lastKnownSequenceNumber = $aggregateRoot->aggregateRootVersion() - count($events);

        $this->eventStore->appendToStream(
            stream: $this->aggregateRootStreamNameInflector->getStreamFor($aggregateRoot::class, $aggregateRoot->aggregateRootId()),
            events: new Events(...array_map(fn(object $event) => new Event(
                $this->eventIdGenerator->generate(),
                $event,
                    new Headers([]),
                ), $events)
            ),
            concurrencyCheck: $lastKnownSequenceNumber === 0 ? new StreamShouldNotExists() : new WithLastKnownSequenceNumber($lastKnownSequenceNumber)
        );
    }
}
