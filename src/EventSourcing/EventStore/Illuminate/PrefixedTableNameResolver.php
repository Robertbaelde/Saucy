<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

use Robertbaelde\Saucy\EventSourcing\EventStore\Stream;

final readonly class PrefixedTableNameResolver implements StreamTableNameResolver
{
    public function __construct(
        public string $prefix,
    )
    {
    }

    public function streamToTableName(Stream $stream): string
    {
        return $this->prefix . $stream->name;
    }
}
