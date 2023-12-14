<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

final readonly class EventIdGenerator
{
    public function __construct()
    {
    }

    public function generate(): string
    {
        return uniqid();
    }
}
