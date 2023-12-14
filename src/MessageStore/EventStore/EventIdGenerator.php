<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

interface EventIdGenerator
{
    public function generate(): string;
}
