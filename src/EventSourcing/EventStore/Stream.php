<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

interface Stream
{
    public function getName(): string;
}
