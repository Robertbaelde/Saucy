<?php

namespace Robertbaelde\Saucy\EventSourcing\Streams;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;

final readonly class PerAggregateTypeStream implements MessageStream
{
    const HEADER_KEY = 'per_aggregate_type_stream_position';

    public function __construct(
        private MessageRepository $messageRepository,
    ) {
    }

    public function getIdentifierFor(Message $message): string
    {
        return $message->aggregateRootType() ?? throw new \Exception('Aggregate root type not set');
    }

    public function getMessagesSince(Message $message, int $position): \Generator
    {
        return $this->iterator_map(
            fn(Message $message, int $generatorPosition) => $message->withHeader(self::HEADER_KEY, $position + $generatorPosition + 1),
            $this->messageRepository->paginate(OffsetCursor::fromOffset($position))
        );
    }

    public function getPositionOfEvent(Message $message): int
    {
        return $message->header(self::HEADER_KEY);
    }

    public function iterator_map(callable $cb, iterable $itr): iterable {
        foreach ($itr as $key => $value) {
            yield $cb($value, $key);
        }
    }
}
