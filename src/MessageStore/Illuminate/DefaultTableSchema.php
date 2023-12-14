<?php

namespace Robertbaelde\Saucy\MessageStore\Illuminate;

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

    public function getMetaDataColumn(): string
    {
        return 'meta_data';
    }

    public function getIdColumn(): string
    {
        return 'id';
    }
}
