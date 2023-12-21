<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

class NamedStream implements Stream
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

    public function getName(): string
    {
        return $this->name;
    }
}
