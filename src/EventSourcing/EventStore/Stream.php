<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

class Stream
{
    public function __construct(
        public string $name,
    )
    {
    }

    public static function withName(string $name): self
    {
        return new self($name);
    }

    public static function all(): self
    {
        return new self('$all');
    }
}
