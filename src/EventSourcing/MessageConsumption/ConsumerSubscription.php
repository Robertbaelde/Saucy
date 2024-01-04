<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
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

    public function trigger(Message $message): void
    {
        // acquire lock
        $streamIdentifier = $this->messageStream->getIdentifierFor($message);

        $this->subscriptionState->acquireLock($streamIdentifier, 60);

        $position = $this->subscriptionState->getPositionInStream($streamIdentifier);

        $messages = $this->messageStream->getMessagesSince($message, $position);

        foreach ($messages as $message) {
            $this->consumer->handle($message);
            $this->subscriptionState->storePositionInStream($streamIdentifier, $this->messageStream->getPositionOfEvent($message));
        }

        $this->subscriptionState->releaseLock($streamIdentifier);
    }

    public function getName(): string
    {
        return get_class($this->consumer);
    }
}
