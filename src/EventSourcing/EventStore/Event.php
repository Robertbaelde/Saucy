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

    public function addHeader(EventStoreHeader | string $header, string $name): self
    {
        return new self(
            $this->eventId,
            $this->payload,
            $this->headers->with($header, $name),
        );
    }
}
