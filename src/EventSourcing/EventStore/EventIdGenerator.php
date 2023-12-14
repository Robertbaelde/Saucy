<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

interface EventIdGenerator
{
    public function generate(): string;
}
