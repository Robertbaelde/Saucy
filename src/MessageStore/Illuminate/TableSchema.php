<?php

namespace Robertbaelde\Saucy\MessageStore\Illuminate;

interface TableSchema
{
    public function getEventIdColumn(): string;
    public function getTypeColumn(): string;
    public function getPayloadColumn(): string;
    public function getMetaDataColumn(): string;

    public function getIdColumn(): string;
}
