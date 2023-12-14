<?php

namespace Robertbaelde\Saucy\MessageStore\ConcurrencyChecks;

final readonly class WithLastKnownEventId implements \Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\ConcurrencyCheck
{
    public function __construct(
        public string $lastEventId
    ) {
    }
}
