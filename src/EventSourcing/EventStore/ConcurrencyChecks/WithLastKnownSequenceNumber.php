<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks;

final readonly class WithLastKnownSequenceNumber implements ConcurrencyCheck
{
    public function __construct(
        public int $lastSequenceNumber
    ) {
    }
}
