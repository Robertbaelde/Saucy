<?php

namespace Robertbaelde\Saucy\MessageStore\Illuminate;

use Robertbaelde\Saucy\MessageStore\Stream;

interface StreamTableNameResolver
{
    public function streamToTableName(Stream $stream): string;
}
