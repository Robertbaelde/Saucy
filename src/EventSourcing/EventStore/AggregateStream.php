<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

class AggregateStream implements Stream
{
    public function __construct(
        public string $aggregateRootType,
        public string $aggregateRootId,
        private string $delimiter = '##',
    )
    {
    }

    public static function withName(string $name, string $delimiter = '##'): self
    {
        [$type, $id] = explode($delimiter, $name, 2);
        return new self($type, $id, $delimiter);
    }

    public static function for(string $aggregateRootTyp, string $aggregateRootId, string $delimiter = '##'): self
    {
        return new self($aggregateRootTyp, $aggregateRootId, $delimiter);
    }

    public function getName(): string
    {
        return $this->aggregateRootType . $this->delimiter . $this->aggregateRootId;
    }
}
