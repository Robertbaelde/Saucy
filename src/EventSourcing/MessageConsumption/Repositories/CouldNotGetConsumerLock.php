<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption\Repositories;

final class CouldNotGetConsumerLock extends \Exception
{
    public static function forStream(string $streamIdentifier, null|\Exception|\Illuminate\Database\QueryException $e = null)
    {
        return new self("Could not get consumer lock for stream {$streamIdentifier}", 0, $e);
    }
}
