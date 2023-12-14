<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

final readonly class UniqIdEventIdGenerator implements EventIdGenerator
{
    public function generate(): string
    {
        return uniqid();
    }
}
