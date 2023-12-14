<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

use Psr\Container\ContainerInterface;
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
        $class = $this->container->get($handler->containerIdentifier);

        $this->container->get($handler->containerIdentifier)->{$handler->methodName}($message);
    }
}
