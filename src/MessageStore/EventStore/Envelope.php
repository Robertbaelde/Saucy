<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

final readonly class Envelope
{
    public function __construct(
        public object $event,
        public Headers $headers,
    )
    {
    }
}
