<?php

namespace Robertbaelde\Saucy\MessageStore\Illuminate;

use Robertbaelde\Saucy\MessageStore\Stream;

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
