<?php

namespace Robertbaelde\Saucy\EventSourcing;

use EventSauce\EventSourcing\AggregateAlwaysAppliesEvents;
use EventSauce\EventSourcing\AggregateRootId;

    /**
     * @template AggregateRootIdType of AggregateRootId
     *
     * @see AggregateRoot
     */
trait AggregateRootBehaviour
{
    use AggregateAlwaysAppliesEvents;

    /** @var AggregateRootIdType */
    private AggregateRootId $aggregateRootId;

    private ?string $checkpoint = null;

    /** @var object[] */
    private array $recordedEvents = [];

    /**
     * @param AggregateRootIdType $aggregateRootId
     */
    private function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;
    }

    /**
     * @return AggregateRootIdType
     */
    public function aggregateRootId(): AggregateRootId
    {
        return $this->aggregateRootId;
    }

    /**
     * @see AggregateRoot::lastCheckpoint
     */
    public function lastCheckpoint(): string|null
    {
        return $this->checkpoint;
    }

    protected function recordThat(object $event): void
    {
        $this->apply($event);
        $this->recordedEvents[] = $event;
    }

    /**
     * @return object[]
     */
    public function releaseEvents(): array
    {
        $releasedEvents = $this->recordedEvents;
        $this->recordedEvents = [];

        return $releasedEvents;
    }

    /**
     * @see AggregateRoot::reconstituteFromEvents
     */
    public static function reconstituteFromEvents(AggregateRootId $aggregateRootId, array $events, string $checkpoint): static
    {
        $aggregateRoot = static::createNewInstance($aggregateRootId);

        /** @var object $event */
        foreach ($events as $event) {
            $aggregateRoot->apply($event);
        }

        $aggregateRoot->checkpoint = $checkpoint;

        return $aggregateRoot;
    }

    /**
     * @param AggregateRootIdType $aggregateRootId
     */
    private static function createNewInstance(AggregateRootId $aggregateRootId): static
    {
        return new static($aggregateRootId);
    }
}
