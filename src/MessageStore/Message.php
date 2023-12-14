<?php

namespace Robertbaelde\Saucy\MessageStore;

final readonly class Message
{
    public function __construct(
        public string $eventId,
        public string $type,
        public array $payload,
        public array $metaData = [],
    )
    {
    }
}
