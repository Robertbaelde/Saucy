<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

use Robertbaelde\Saucy\EventSourcing\EventStore\Stream;

final readonly class HashStreamTableNameResolver implements StreamTableNameResolver
{
    public function streamToTableName(Stream $stream): string
    {
        return '__stream_' . sha1($stream->name);
    }
}
