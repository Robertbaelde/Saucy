<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

final readonly class UniqIdEventIdGenerator implements EventIdGenerator
{
    public function generate(): string
    {
        return uniqid();
    }
}
