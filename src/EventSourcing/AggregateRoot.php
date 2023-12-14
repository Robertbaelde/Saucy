<?php

namespace Robertbaelde\Saucy\EventSourcing;

use EventSauce\EventSourcing\AggregateRootId;


/**
 * @template-covariant AggregateRootIdType of AggregateRootId
 *
 * @see AggregateRootBehaviour
 */
interface AggregateRoot
{
    /**
     * @return AggregateRootIdType
     */
    public function aggregateRootId(): AggregateRootId;

    public function lastCheckpoint(): string|null;

    /**
     * @return object[]
     */
    public function releaseEvents(): array;

    /**
     * @param AggregateRootIdType               $aggregateRootId
     * @param object[]                          $events
     */
    public static function reconstituteFromEvents(AggregateRootId $aggregateRootId, array $events, string $checkpoint): static;
}
