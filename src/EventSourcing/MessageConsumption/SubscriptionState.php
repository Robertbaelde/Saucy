<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption;

interface SubscriptionState
{
    public function acquireLock(string $streamIdentifier, int $ttl): bool;

    public function getPositionInStream(string $streamIdentifier): int;

    public function storePositionInStream(string $streamIdentifier, int $position): void;

    public function releaseLock(string $streamIdentifier): void;
}
