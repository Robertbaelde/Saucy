<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

use Symfony\Component\Uid\Ulid;

final readonly class UlidEventIdGenerator implements EventIdGenerator
{
    public function generate(): string
    {
        return Ulid::generate();
    }
}
