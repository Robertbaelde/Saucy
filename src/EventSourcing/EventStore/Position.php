<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

final readonly class Position
{
    public function __construct(
        public int $position,
    )
    {
    }
}
