<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\ConcurrencyCheck;
use Robertbaelde\Saucy\EventSourcing\EventStore\Events;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStoreHeader;
use Robertbaelde\Saucy\EventSourcing\EventStore\Position;
use Robertbaelde\Saucy\EventSourcing\EventStore\Serialization\EventSerializer;
use Robertbaelde\Saucy\EventSourcing\EventStore\NamedStream;
use Robertbaelde\Saucy\EventSourcing\EventStore\Stream;

final readonly class IlluminateAllStreamEventStore implements EventStore
{
    private NamedStream $stream;

    public function __construct(
        private IlluminateEventStore | StreamPerTypeIlluminateEventStore $eventStore,
        private ConnectionInterface $connection,
        private StreamTableNameResolver $streamTableNameResolver,
        private TableSchema $tableSchema,
        private EventSerializer $eventSerializer,
        ?NamedStream $stream = null,
    )
    {
        $this->stream = $stream ?? NamedStream::all();
    }

    public function appendToStream(Stream $stream, Events $events, ?ConcurrencyCheck $concurrencyCheck = null): void
    {
        try {
            $this->connection->beginTransaction();
            $this->eventStore->appendToStream($stream, $events, $concurrencyCheck);
            $this->insertIntoAll($stream, $events);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function getEvents(Stream $stream, ?Position $position = null, ?int $limit = null): Events
    {
        return $this->eventStore->getEvents($stream, $position, $limit);
    }

    private function insertIntoAll(Stream $originalStream, Events $events): void
    {
        $this->connection->table($this->streamTableNameResolver->streamToTableName($this->stream))
            ->insertOrIgnore(
                $this->eventSerializer->serializeEvents($events->addHeader(EventStoreHeader::ORIGINAL_STREAM, $originalStream->getName()), $this->tableSchema)
            );
    }

    public function migrate(): void
    {
        $this->connection->getSchemaBuilder()->create($this->streamTableNameResolver->streamToTableName($this->stream), function (Blueprint $table) {
            $table->id($this->tableSchema->getSequenceColumn());
            $table->string($this->tableSchema->getEventIdColumn());
            $table->string($this->tableSchema->getTypeColumn());
            $table->json($this->tableSchema->getPayloadColumn());
            $table->json($this->tableSchema->getHeadersColumn());
            $table->unique($this->tableSchema->getEventIdColumn(), 'event_id_unique');
        });
    }
}
