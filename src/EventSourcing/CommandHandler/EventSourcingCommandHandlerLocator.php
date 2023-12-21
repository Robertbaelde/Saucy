<?php

namespace Robertbaelde\Saucy\EventSourcing\CommandHandler;

use Robertbaelde\Saucy\MessageBus\CommandBus\CommandHandlerLocator;
use Robertbaelde\Saucy\MessageBus\CommandBus\Handler;

final readonly class EventSourcingCommandHandlerLocator implements CommandHandlerLocator
{
    public function __construct(
        private CommandHandlerLocator $commandHandlerLocator,
        private AggregateRootDirectory $aggregateRootDirectory,
    )
    {
    }

    public function getHandler(object $message): Handler
    {
        $handler = $this->commandHandlerLocator->getHandler($message);
        if(!$this->aggregateRootDirectory->isAggregateRoot($handler)) {
            return $handler;
        }

        return new Handler(
            AggregateRootCommandHandler::class,
            $handler->isStatic ? 'handleStatic' : 'handle',
            $handler->isStatic,
        );
    }
}
