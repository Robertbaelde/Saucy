<?php

namespace Robertbaelde\Saucy\EventSourcing\MessageConsumption;

final class ConsumerDictionary
{
    /**
     * @var array<class-string, <array<ConsumerSubscription>>
     */
    private array $consumersByAggregate = [];

    public function __construct(array $consumers = [])
    {
        foreach ($consumers as $aggregateRootClass => $consumer) {
            $this->register($aggregateRootClass, $consumer);
        }
    }

    public function register(string $aggregateRootType, ConsumerSubscription $consumerSubscription): void
    {
        if(!array_key_exists($aggregateRootType, $this->consumersByAggregate)){
            $this->consumersByAggregate[$aggregateRootType] = [];
        }
        $this->consumersByAggregate[$aggregateRootType][$consumerSubscription->getName()] = $consumerSubscription;
    }

    /**
     * @param class-string $aggregateRootType
     * @return array<ConsumerSubscription>
     */
    public function getConsumersForAggregate(string $aggregateRootType): array
    {
        return $this->consumersByAggregate[$aggregateRootType] ?? [];
    }

    public function getConsumerForAggregateByName(string $aggregateRootType, string $consumerName): ConsumerSubscription
    {
        return $this->consumersByAggregate[$aggregateRootType][$consumerName] ?? throw new \Exception("Consumer not found");
    }

}
