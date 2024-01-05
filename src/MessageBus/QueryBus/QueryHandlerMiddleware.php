<?php

namespace Robertbaelde\Saucy\MessageBus\QueryBus;

use Psr\Container\ContainerInterface;
use Robertbaelde\Saucy\MessageBus\Middleware;

final readonly class QueryHandlerMiddleware implements Middleware
{
    public function __construct(
        private ContainerInterface $container,
        private MappedQueryHandlerLocator $queryHandlerLocator,
    )
    {
    }

    public function run(object $message, callable $next): mixed
    {
        $location = $this->queryHandlerLocator->getForQuery($message);
        return $this->container->get($location->containerIdentifier)->{$location->methodName}($message);
    }
}
