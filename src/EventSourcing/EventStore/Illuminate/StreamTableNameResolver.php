<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

use Robertbaelde\Saucy\EventSourcing\EventStore\NamedStream;
use Robertbaelde\Saucy\EventSourcing\EventStore\Stream;

interface StreamTableNameResolver
{
    public function streamToTableName(Stream $stream): string;
}
