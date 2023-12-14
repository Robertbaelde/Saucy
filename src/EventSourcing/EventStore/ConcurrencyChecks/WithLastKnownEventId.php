<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks;

final readonly class WithLastKnownEventId implements ConcurrencyCheck
{
    public function __construct(
        public string $lastEventId
    ) {
    }
}
