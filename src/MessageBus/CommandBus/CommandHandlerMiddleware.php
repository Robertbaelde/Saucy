<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

use Psr\Container\ContainerInterface;
use Robertbaelde\Saucy\MessageBus\CommandBus\Queue\ProcessCommand;
use Robertbaelde\Saucy\MessageBus\Middleware;

final readonly class CommandHandlerMiddleware implements Middleware
{
    public function __construct(
        private ContainerInterface $container,
        private MappedCommandHandlerLocator $handlerLocator,
    )
    {
    }

    public function run(object $message, callable $next): void
    {
        $handler = $this->handlerLocator->getHandler($message);
        if($handler->queue) {
            dispatch(new ProcessCommand($handler, $message));
            return;
        }
        $this->container->get($handler->containerIdentifier)->{$handler->methodName}($message);
    }
}
