<?php

namespace Robertbaelde\Saucy\MessageStore\Illuminate;

use Robertbaelde\Saucy\MessageStore\Stream;

final readonly class HashStreamTableNameResolver implements StreamTableNameResolver
{
    public function streamToTableName(Stream $stream): string
    {
        return '__stream_' . sha1($stream->name);
    }
}
