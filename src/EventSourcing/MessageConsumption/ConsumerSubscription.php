<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Robertbaelde\Saucy\EventSourcing\Streams\MessageStream;

final readonly class ConsumerSubscription
{
    public function __construct(
        private MessageStream $messageStream,
        private SubscriptionState $subscriptionState,
        private MessageConsumer $consumer,
    )
    {
    }

    public function reset(): void
    {
        $this->consumer->reset();
    }

    public function getIdentifier(Message $message): string
    {
        return $this->getName() . '_' . $this->messageStream->getIdentifierFor($message);
    }

    /**
     * @throws LockTimeoutException
     */
    public function trigger(string $consumerSubscriptionIdentifier): void
    {
        // acquire lock

        retry(5, function () use ($consumerSubscriptionIdentifier) {
            $this->subscriptionState->acquireLock($consumerSubscriptionIdentifier, 30);
        }, rand(50, 1000));


        $position = $this->subscriptionState->getPositionInStream($consumerSubscriptionIdentifier);

        $streamIdentifier = str_replace($this->getName() . '_', '', $consumerSubscriptionIdentifier);
        $messages = $this->messageStream->getMessagesSince($streamIdentifier, $position);

        DB::beginTransaction();

        try {
            foreach ($messages as $message) {
                $this->consumer->handle($message);
            }

            $this->subscriptionState->storePositionInStream($consumerSubscriptionIdentifier, $this->messageStream->getPositionOfEvent($message));
            $this->subscriptionState->releaseLock($consumerSubscriptionIdentifier);

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->subscriptionState->releaseLock($consumerSubscriptionIdentifier);
            throw $e;
        }


        DB::commit();

    }

    public function getPosition(string $streamIdentifier): int
    {
        return $this->subscriptionState->getPositionInStream($streamIdentifier);
    }

    public function shouldProcessMoreMessages(string $streamIdentifier): bool
    {
        $position = $this->getPosition($streamIdentifier);
        foreach ($this->messageStream->getMessagesSince($streamIdentifier, $position) as $message) {
            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return Str::of(get_class($this->consumer))->afterLast('\\')->snake()->toString();
    }
}
