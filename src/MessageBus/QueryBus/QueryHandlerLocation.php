<?php

namespace Robertbaelde\Saucy\MessageBus\QueryBus;

final readonly class QueryHandlerLocation
{
    public function __construct(
        public string $containerIdentifier,
        public string $methodName,
    )
    {
    }
}
