<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

final readonly class DefaultTableSchema implements TableSchema
{
    public function getEventIdColumn(): string
    {
        return 'event_id';
    }

    public function getTypeColumn(): string
    {
        return 'type';
    }

    public function getPayloadColumn(): string
    {
        return 'payload';
    }

    public function getHeadersColumn(): string
    {
        return 'headers';
    }

    public function getSequenceColumn(): string
    {
        return 'sequence';
    }
}
