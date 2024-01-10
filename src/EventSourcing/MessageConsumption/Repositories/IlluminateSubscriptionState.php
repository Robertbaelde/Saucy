<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption\Repositories;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\SubscriptionState;

final readonly class IlluminateSubscriptionState implements SubscriptionState
{
    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @throws CouldNotGetConsumerLock
     */
    public function acquireLock(string $streamIdentifier, int $ttl): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->table('stream_positions')
                ->where('stream_identifier', $streamIdentifier)
                ->lockForUpdate()
                ->first();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw CouldNotGetConsumerLock::forStream($streamIdentifier, $e);
        }


        if ($lock && $lock->lock_expiration_time && Carbon::parse($lock->lock_expiration_time)->isFuture()) {
            try {
                $this->db->rollBack();
            } catch (\Exception $e) {
            }
            throw CouldNotGetConsumerLock::forStream($streamIdentifier);
        }

        try {
            $this->db->table('stream_positions')
                ->updateOrInsert(
                ['stream_identifier' => $streamIdentifier],
                ['lock_expiration_time' => now()->addSeconds($ttl)]
                );
        } catch (QueryException $e) {
            $this->db->rollBack();
            throw CouldNotGetConsumerLock::forStream($streamIdentifier);
        }

        $this->db->commit();
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
