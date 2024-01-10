<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption;

interface ConsumerLock
{
    /**
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function acquireLock(string $streamIdentifier, int $ttl): void;

    public function releaseLock(string $streamIdentifier): void;
}
