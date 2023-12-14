<?php

namespace Robertbaelde\Saucy\Tests\MessageBus\CommandBus\Fixtures;

final readonly class InMemoryContainer implements \Psr\Container\ContainerInterface
{
    public function __construct(
        public array $map,
    )
    {
    }

    public function get(string $id)
    {
        return $this->map[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->map[$id]);
    }
}
