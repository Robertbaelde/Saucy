<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

interface TableSchema
{
    public function getSequenceColumn(): string;

    public function getEventIdColumn(): string;
    public function getTypeColumn(): string;
    public function getPayloadColumn(): string;
    public function getHeadersColumn(): string;


}
