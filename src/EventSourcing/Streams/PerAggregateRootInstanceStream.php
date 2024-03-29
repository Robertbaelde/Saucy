<?php

namespace Robertbaelde\Saucy\EventSourcing\Streams;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\AnonymousAggregateRootId;

final readonly class PerAggregateRootInstanceStream implements MessageStream
{
    public function __construct(
        private MessageRepository $messageRepository,
    )
    {
    }

    public function getIdentifierFor(Message $message): string
    {
        return $message->aggregateRootId()?->toString() ?? throw new \Exception('Aggregate root id not set');
    }

    public function getMessagesSince(string $streamIdentifier, int $position): \Generator
    {
        return $this->messageRepository->retrieveAllAfterVersion(AnonymousAggregateRootId::fromString($streamIdentifier), $position);
    }

    public function getPositionOfEvent(Message $message): int
    {
        return $message->aggregateVersion();
    }
}
