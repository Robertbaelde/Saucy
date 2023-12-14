<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

final readonly class Event
{
    public function __construct(
        public string $eventId,
        public object $payload,
        public Headers $headers,
    ) {
    }
}
