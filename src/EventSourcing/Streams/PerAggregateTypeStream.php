<?php

namespace Robertbaelde\Saucy\EventSourcing\Streams;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\PaginationCursor;

final class PerAggregateTypeStream implements MessageStream
{
    private OffsetCursor $nextCursor;

    public function __construct(
        private readonly MessageRepository $messageRepository,
    ) {
    }

    public function getIdentifierFor(Message $message): string
    {
        return $message->aggregateRootType() ?? throw new \Exception('Aggregate root type not set');
    }

    public function getMessagesSince(string $streamIdentifier, int $position): \Generator
    {
        $messages = $this->messageRepository->paginate(OffsetCursor::fromOffset($position));
        foreach ($messages as $message){
            yield $message;
        }
        $this->nextCursor = $messages->getReturn();
        return $messages->getReturn();
    }

    public function getPositionOfEvent(Message $message): int
    {
        if(!isset($this->nextCursor)){
            throw new \Exception('No next cursor set');
        }
        return $this->nextCursor->offset();
    }

}
