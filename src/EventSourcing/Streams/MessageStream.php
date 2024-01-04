<?php

namespace Robertbaelde\Saucy\EventSourcing\Streams;

use EventSauce\EventSourcing\Message;
use Generator;

interface MessageStream
{

    public function getIdentifierFor(Message $message): string;

    public function getMessagesSince(Message $message, int $position): Generator;

    public function getPositionOfEvent(Message $message): int;
}
