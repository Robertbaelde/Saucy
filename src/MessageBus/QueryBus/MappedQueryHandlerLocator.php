<?php

namespace Robertbaelde\Saucy\MessageBus\QueryBus;

final readonly class MappedQueryHandlerLocator
{
    /**
     * @param array<class-string, QueryHandlerLocation> $quweryHandlerMap
     */
    public function __construct(
        private array $queryHandlerMap,
    )
    {
    }

    public function getForQuery(object $message): QueryHandlerLocation
    {
        $messageClass = get_class($message);
        if(!array_key_exists($messageClass, $this->queryHandlerMap)) {
            throw new \InvalidArgumentException("No handler found for query {$messageClass}");
        }
        return $this->queryHandlerMap[$messageClass];
    }
}
