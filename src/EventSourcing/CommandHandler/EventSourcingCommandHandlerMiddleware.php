<?php

namespace Robertbaelde\Saucy\EventSourcing\CommandHandler;

use Psr\Container\ContainerInterface;
use Robertbaelde\Saucy\MessageBus\CommandBus\Handler;
use Robertbaelde\Saucy\MessageBus\CommandBus\MappedCommandHandlerLocator;
use Robertbaelde\Saucy\MessageBus\CommandBus\Queue\ProcessCommand;
use Robertbaelde\Saucy\MessageBus\Middleware;

final readonly class EventSourcingCommandHandlerMiddleware implements Middleware
{
    public function __construct(
        private MappedCommandHandlerLocator $handlerLocator,
        private AggregateRootDirectory $aggregateRootDirectory,
        private ContainerInterface $container,
    )
    {
    }

    public function run(object $message, callable $next): void
    {
        try {
            $handler = $this->handlerLocator->getHandler($message);
        } catch (\Exception $e) {
            throw $e;
            $next($message);
            return;
        }

        if(!$this->aggregateRootDirectory->classNameIsAggregateRoot($handler->containerIdentifier)){
            $next($message);
            return;
        }


        if($handler->isStatic){
            $command = new HandleStaticAggregateRoot($handler->containerIdentifier, $handler->methodName, $message);
            $aggregateCommandHandler = new Handler(AggregateRootCommandHandler::class, 'handleStatic', false, null, null, $handler->queue);
            if($aggregateCommandHandler->queue) {
                dispatch(new ProcessCommand($aggregateCommandHandler, $command));
                return;
            }
            $this->container->get($aggregateCommandHandler->containerIdentifier)->{$aggregateCommandHandler->methodName}($command);
            return;
        }

        if($handler->aggregateRootIdProperty === null && $handler->aggregateRootIdCommandMethod === null){
            $next($message);
            return;
        }

        $id = $handler->aggregateRootIdProperty !== null ? $message->{$handler->aggregateRootIdProperty} : $message->{$handler->aggregateRootIdCommandMethod}();

        $command = new HandleAggregateRoot($id, $handler->containerIdentifier, $handler->methodName, $message);
        $aggregateCommandHandler = new Handler(AggregateRootCommandHandler::class, 'handle', false, null, null, $handler->queue);

        if($aggregateCommandHandler->queue) {
            dispatch(new ProcessCommand($aggregateCommandHandler, $command));
            return;
        }
        $this->container->get($aggregateCommandHandler->containerIdentifier)->{$aggregateCommandHandler->methodName}($command);
    }
}
