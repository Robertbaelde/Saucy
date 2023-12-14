<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

enum EventStoreHeader: string
{
    case EVENT_SEQUENCE = 'event_sequence';
}
