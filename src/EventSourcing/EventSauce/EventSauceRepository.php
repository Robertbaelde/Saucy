<?php

namespace Robertbaelde\Saucy\EventSourcing\EventSauce;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\IdEncoding\StringIdEncoder;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateMessageRepository;
use Illuminate\Database\Connection;

final class EventSauceRepository
{

    /**
     * @var array<class-string, EventSourcedAggregateRootRepository>
     */
    private array $aggregateRootRepositories = [];

    public function __construct(
        private Connection $connection,
        private ClassNameInflector $aggregateRootNameInflector,
        private EventStreamTableMigrator $eventStreamTableMigrator,
        private MessageSerializer $serializer,
        private MessageDecorator $decorator,
        private MessageDispatcher $dispatcher,
    )
    {
    }

    /**
     * @template T of AggregateRoot
     * @param class-string<T> $aggregateRootClass
     * @return T
     */
    public function retrieve(string $aggregateRootClass, AggregateRootId $aggregateRootId): object
    {
        if(!array_key_exists($aggregateRootClass, $this->aggregateRootRepositories)){
            $this->aggregateRootRepositories[$aggregateRootClass] = $this->buildAggregateRootRepository($aggregateRootClass);
        }

        return $this->aggregateRootRepositories[$aggregateRootClass]->retrieve($aggregateRootId);
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $className = $aggregateRoot::class;
        if(!array_key_exists($className, $this->aggregateRootRepositories)){
            $this->aggregateRootRepositories[$className] = $this->buildAggregateRootRepository($className);
        }

        $this->aggregateRootRepositories[$className]->persist($aggregateRoot);

    }

    private function buildAggregateRootRepository(string $aggregateRootClass): EventSourcedAggregateRootRepository
    {
        $tableName = $this->eventStreamTableMigrator->ensureTableExistsForAndReturnTableName($aggregateRootClass);

        return new EventSourcedAggregateRootRepository(
            aggregateRootClassName: $aggregateRootClass,
            messageRepository: new IlluminateMessageRepository(
                connection: $this->connection,
                tableName: $tableName,
                serializer: $this->serializer,
                aggregateRootIdEncoder: new StringIdEncoder(),
            ),
            dispatcher: $this->dispatcher,
            decorator: $this->decorator,
            classNameInflector: $this->aggregateRootNameInflector,
        );
    }
}
