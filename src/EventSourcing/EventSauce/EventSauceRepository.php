<?php

namespace Robertbaelde\Saucy\EventSourcing\EventSauce;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\IdEncoding\StringIdEncoder;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateMessageRepository;
use Illuminate\Database\Connection;
use Robertbaelde\Saucy\EventSourcing\Streams\PerAggregateRootInstanceStream;

final class EventSauceRepository
{

    /**
     * @var array<class-string, EventSourcedAggregateRootRepository>
     */
    private array $aggregateRootRepositories = [];

    /**
     * @var array<class-string, MessageRepository>
     */
    private array $aggregateRootMessageRepositories = [];

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
        $this->eventStreamTableMigrator->ensureTableExistsForAndReturnTableName($aggregateRootClass);
        if(!array_key_exists($aggregateRootClass, $this->aggregateRootRepositories)){
            $this->aggregateRootRepositories[$aggregateRootClass] = $this->buildAggregateRootRepository($aggregateRootClass);
        }

        return $this->aggregateRootRepositories[$aggregateRootClass]->retrieve($aggregateRootId);
    }

    public function persist(AggregateRoot $aggregateRoot): void
    {
        $this->eventStreamTableMigrator->ensureTableExistsForAndReturnTableName(get_class($aggregateRoot));
        $className = $aggregateRoot::class;
        if(!array_key_exists($className, $this->aggregateRootRepositories)){
            $this->aggregateRootRepositories[$className] = $this->buildAggregateRootRepository($className);
        }

        $this->aggregateRootRepositories[$className]->persist($aggregateRoot);

    }

    public function getMessageRepositoryFor(string $aggregateRootClass): MessageRepository
    {
        return $this->getMessageRepository($aggregateRootClass);
    }

    private function buildAggregateRootRepository(string $aggregateRootClass): EventSourcedAggregateRootRepository
    {
        return new EventSourcedAggregateRootRepository(
            aggregateRootClassName: $aggregateRootClass,
            messageRepository: $this->getMessageRepository($aggregateRootClass),
            dispatcher: $this->dispatcher,
            decorator: $this->decorator,
            classNameInflector: $this->aggregateRootNameInflector,
        );
    }

    private function getMessageRepository(string $aggregateRootClass): MessageRepository
    {
        if(array_key_exists($aggregateRootClass, $this->aggregateRootMessageRepositories)){
            return $this->aggregateRootMessageRepositories[$aggregateRootClass];
        }

        $tableName = $this->eventStreamTableMigrator->ensureTableExistsForAndReturnTableName($aggregateRootClass);

        $messageRepository = new IlluminateMessageRepository(
            connection: $this->connection,
            tableName: $tableName,
            serializer: $this->serializer,
            aggregateRootIdEncoder: new StringIdEncoder(),
        );
        $this->aggregateRootMessageRepositories[$aggregateRootClass] = $messageRepository;
        return $messageRepository;
    }
}
