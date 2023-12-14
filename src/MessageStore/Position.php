<?php

namespace Robertbaelde\Saucy\MessageStore;

final readonly class Position
{
    public function __construct(
        public int $position,
    )
    {
    }
}
