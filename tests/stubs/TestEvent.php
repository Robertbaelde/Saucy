<?php

namespace Robertbaelde\Saucy\Tests\stubs;

use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Robertbaelde\Saucy\Attributes\Event;

#[Event('test_event')]
final readonly class TestEvent implements SerializablePayload
{
    public function __construct()
    {
    }

    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload): static
    {
        return new self();
    }
}
