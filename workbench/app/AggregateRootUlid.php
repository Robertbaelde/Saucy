<?php

namespace Workbench\App;

use EventSauce\EventSourcing\AggregateRootId;
use Symfony\Component\Uid\Ulid as UlidGenerator;

class AggregateRootUlid implements AggregateRootId
{
    private function __construct(private string $id)
    {
        if(!UlidGenerator::isValid($id)) {
            throw new \InvalidArgumentException('Invalid ulid');
        }
    }

    public static function generate(): static
    {
        return new static(UlidGenerator::generate());
    }

    public function toString(): string
    {
        return $this->id;
    }

    public static function fromString(string $aggregateRootId): static
    {
        return new static($aggregateRootId);
    }

    public function equals(self $that): bool
    {
        return $this->id === $that->id;
    }
}
