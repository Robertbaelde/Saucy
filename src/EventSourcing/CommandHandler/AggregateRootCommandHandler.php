<?php

namespace Robertbaelde\Saucy\EventSourcing\CommandHandler;

use Robertbaelde\Saucy\EventSourcing\EventSauce\EventSauceRepository;

final readonly class AggregateRootCommandHandler
{
    public function __construct(
        private EventSauceRepository $eventSauceRepository
    )
    {
    }

    public function handle(HandleAggregateRoot $handleAggregateRoot): void
    {
        $aggregateRoot = $this->eventSauceRepository->retrieve($handleAggregateRoot->aggregateRootClass, $handleAggregateRoot->aggregateRootId);
        $aggregateRoot->{$handleAggregateRoot->method}($handleAggregateRoot->command);
        $this->eventSauceRepository->persist($aggregateRoot);
    }

    public function handleStatic(HandleStaticAggregateRoot $handleStaticAggregateRoot): void
    {
        $this->eventSauceRepository->persist($handleStaticAggregateRoot->aggregateRootClass::{$handleStaticAggregateRoot->method}($handleStaticAggregateRoot->command));
    }
}
