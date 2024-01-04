<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption\Repositories;

use Illuminate\Database\ConnectionInterface;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\SubscriptionState;

final readonly class IlluminateSubscriptionState implements SubscriptionState
{
    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function acquireLock(string $streamIdentifier, int $ttl): bool
    {
        $this->db->beginTransaction();
        $lock = $this->db->table('stream_positions')
            ->where('stream_identifier', $streamIdentifier)
            ->lockForUpdate()
            ->first();

        if ($lock && $lock->lock_expiration_time && $lock->lock_expiration_time->isFuture()) {
            $this->db->rollBack();
            return false;
        }

        $this->db->table('stream_positions')->updateOrInsert(
            ['stream_identifier' => $streamIdentifier],
            ['lock_expiration_time' => now()->addSeconds($ttl)]
        );

        $this->db->commit();

        return true;
    }

    public function getPositionInStream(string $streamIdentifier): int
    {
        $position = $this->db->table('stream_positions')
            ->where('stream_identifier', $streamIdentifier)
            ->value('position');

        return $position ?: 0;
    }

    public function storePositionInStream(string $streamIdentifier, int $position): void
    {
        $this->db->table('stream_positions')->updateOrInsert(
            ['stream_identifier' => $streamIdentifier],
            ['position' => $position]
        );
    }

    public function releaseLock(string $streamIdentifier): void
    {
        $this->db->table('stream_positions')
            ->where('stream_identifier', $streamIdentifier)
            ->update(['lock_expiration_time' => null]);
    }
}
