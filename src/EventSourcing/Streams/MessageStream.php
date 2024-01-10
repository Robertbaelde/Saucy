<?php

namespace Robertbaelde\Saucy\EventSourcing\Streams;

use EventSauce\EventSourcing\Message;
use Generator;

interface MessageStream
{

    public function getIdentifierFor(Message $message): string;

    public function getMessagesSince(string $streamIdentifier, int $position): Generator;

    public function getPositionOfEvent(Message $message): int;
}
