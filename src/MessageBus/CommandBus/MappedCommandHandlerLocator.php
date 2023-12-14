<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

final readonly class MappedCommandHandlerLocator implements CommandHandlerLocator
{
    /**
     * @param array<string, Handler> $commandHandlerMap
     */
    public function __construct(
        private array $commandHandlerMap,
    ) {
    }

    public function getHandler(object $message): Handler
    {
        $className = get_class($message);

        if (!isset($this->commandHandlerMap[$className])) {
            throw new \Exception('No handler found for ' . $className);
        }

        return $this->commandHandlerMap[$className];
    }
}
