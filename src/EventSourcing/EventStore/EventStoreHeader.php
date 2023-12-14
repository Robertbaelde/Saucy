<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

enum EventStoreHeader: string
{
    case EVENT_SEQUENCE = 'event_sequence';
    case ORIGINAL_STREAM = 'original_stream';
}
