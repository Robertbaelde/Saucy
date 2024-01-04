<?php

namespace Robertbaelde\Saucy\EventSourcing\CommandHandler;

final readonly class AggregateRootDirectory
{
    /**
     * @param array<string, <class-string>> $aggregateRoots
     */
    public function __construct(
        private array $aggregateRoots,
    )
    {
    }
    public function isAggregateRoot(object $object): bool
    {
        return $this->classNameIsAggregateRoot(get_class($object));
    }

    public function classNameIsAggregateRoot(string $className): bool
    {
        return in_array($className, $this->aggregateRoots, true);
    }

    public function getAggregateRootName(string|object $object): string
    {
        if(is_object($object)){
            $object = get_class($object);
        }
        return array_search($object, $this->aggregateRoots, true);
    }

    /**
     * @param string $name
     * @return class-string
     */
    public function getAggregateRootClass(string $name): string
    {
        return $this->aggregateRoots[$name];
    }
}
