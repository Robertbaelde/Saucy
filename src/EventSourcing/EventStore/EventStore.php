<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\ConcurrencyCheck;

interface EventStore
{
    public function appendToStream(Stream $stream, Events $events, ?ConcurrencyCheck $concurrencyCheck = null): void;

    public function getEvents(Stream $stream, ?Position $position= null, ?int $limit = null): Events;
}
