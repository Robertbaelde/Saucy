<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

final readonly class Headers
{
    public function __construct()
    {
    }

    public function toArray(): array
    {
        return [];
    }

    public static function fromArray(array $headers): self
    {
       return new self();
    }
}
