<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Robertbaelde\Saucy\EventSourcing\EventStore\AggregateStream;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\ConcurrencyCheck;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\StreamShouldNotExists;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\WithLastKnownEventId;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\WithLastKnownSequenceNumber;
use Robertbaelde\Saucy\EventSourcing\EventStore\Events;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\Exceptions\ConcurrencyException;
use Robertbaelde\Saucy\EventSourcing\EventStore\Exceptions\StreamExistsException;
use Robertbaelde\Saucy\EventSourcing\EventStore\Position;
use Robertbaelde\Saucy\EventSourcing\EventStore\Serialization\EventSerializer;
use Robertbaelde\Saucy\EventSourcing\EventStore\NamedStream;
use Robertbaelde\Saucy\EventSourcing\EventStore\Stream;

final readonly class StreamPerTypeIlluminateEventStore implements EventStore
{
    public function __construct(
        private ConnectionInterface $connection,
        private StreamTableNameResolver $streamTableNameResolver,
        private TableSchema $tableSchema,
        private EventSerializer $eventSerializer,
    )
    {
        if(!$this->tableSchema instanceof StreamPerTypeTableSchema){
            throw new \Exception('TableSchema is not an instance of StreamPerTypeTableSchema');
        }
    }

    /**
     * @throws ConcurrencyException
     * @throws \Exception
     */
    public function appendToStream(Stream $stream, Events $events, ?ConcurrencyCheck $concurrencyCheck = null): void
    {
        if(!$stream instanceof AggregateStream){
            throw new \Exception('Stream is not an instance of AggregateStream');
        }

        // root stream
        $aggregateTypeStream = NamedStream::withName($stream->aggregateRootType);

        $shouldRestartTransaction = false;
        $tableName = $this->streamTableNameResolver->streamToTableName($aggregateTypeStream);

        $insertValues = $this->eventSerializer->serializeEvents($events, $this->tableSchema);

        // add aggregate root id to each event
        foreach ($insertValues as &$insertValue){
            $insertValue[$this->tableSchema->getAggregateRootIdColumn()] = $stream->aggregateRootId;
        }

        if($concurrencyCheck instanceof StreamShouldNotExists){
            if($this->streamExists($tableName) && $this->getStreamQuery($stream)->count() !== 0){
                throw new StreamExistsException();
            }
        }

        try {
            if($this->connection->transactionLevel() > 0){
                $shouldRestartTransaction = true;
            }
            $this->insert($tableName, $insertValues, $concurrencyCheck, $stream->aggregateRootId);
        } catch (QueryException $queryException) {
            if($queryException->getCode() !== '42S02'){
                throw $queryException;
            }

            $this->connection->commit();
            $this->createStreamTable($tableName);
            // restart transaction?
            if($shouldRestartTransaction){
                $this->connection->beginTransaction();
            }
            $this->appendToStream($stream, $events, $concurrencyCheck);
        }
    }

    public function getEvents(Stream $stream, ?Position $position = null, ?int $limit = null): Events
    {
        if(!$stream instanceof AggregateStream){
            throw new \Exception('Stream is not an instance of AggregateStream');
        }

        try {
            return $this->eventSerializer->deserializeEvents(
                $this->getStreamQuery($stream)->get()->toArray(),
                $this->tableSchema
            );
        } catch (QueryException $queryException) {
            if($queryException->getCode() !== '42S02'){
                throw $queryException;
            }
            return new Events();
        }
    }

    private function getStreamQuery(AggregateStream $stream): \Illuminate\Database\Query\Builder
    {
        return $this->connection->table($this->streamTableNameResolver->streamToTableName(NamedStream::withName($stream->aggregateRootType)))
            ->where($this->tableSchema->getAggregateRootIdColumn(), $stream->aggregateRootId)
            ->orderBy($this->tableSchema->getSequenceColumn());
    }

    private function createStreamTable(string $tableName): void
    {
        if(!$this->connection instanceof Connection){
            throw new \Exception('Connection is not an instance of Connection');
        }
        $this->connection->getSchemaBuilder()->create($tableName, function (Blueprint $table) {
            $table->id($this->tableSchema->getSequenceColumn());
            $table->string($this->tableSchema->getEventIdColumn());
            $table->string($this->tableSchema->getAggregateRootIdColumn());
            $table->string($this->tableSchema->getTypeColumn());
            $table->json($this->tableSchema->getPayloadColumn());
            $table->json($this->tableSchema->getHeadersColumn());
            $table->unique($this->tableSchema->getEventIdColumn(), 'event_id_unique');
            $table->index([$this->tableSchema->getAggregateRootIdColumn(), $this->tableSchema->getSequenceColumn()], 'aggregate_root_id_index');
        });
    }

    private function streamExists(string $tableName): bool
    {
        return $this->connection->getSchemaBuilder()->hasTable($tableName);
    }

    private function insert(string $tableName, array $insertValues, ?ConcurrencyCheck $concurrencyCheck, string $aggregateRootId): void
    {

        if(!$concurrencyCheck instanceof WithLastKnownEventId && !$concurrencyCheck instanceof WithLastKnownSequenceNumber){
            $this->connection->table($tableName)->insertOrIgnore($insertValues);
            return;
        }

        $columns = array_keys($insertValues[0]);

        // Constructing a union sub query for each row
        $subQuerySqlParts = [];
        foreach ($insertValues as $row) {
            $valuesString = implode(", ", array_map(function ($value) {
                return $this->connection->getPdo()->quote($value);
            }, array_values($row)));

            $subQuerySqlParts[] = "SELECT $valuesString FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `$tableName` WHERE `{$this->tableSchema->getEventIdColumn()}` = '{$row[$this->tableSchema->getEventIdColumn()]}')";
        }

        // Combine all subqueries with UNION ALL
        $subQuerySql = implode(" UNION ALL ", $subQuerySqlParts);

        // The final subquery with the conditional check
        $finalSubQuery = $this->connection->table($this->connection->raw("($subQuerySql) as sub"));

        if($concurrencyCheck instanceof WithLastKnownEventId){
            $finalSubQuery = $finalSubQuery->whereRaw("(SELECT `{$this->tableSchema->getEventIdColumn()}` FROM `$tableName` WHERE `{$this->tableSchema->getAggregateRootIdColumn()}` = ? ORDER BY `{$this->tableSchema->getSequenceColumn()}` DESC LIMIT 1) = ?", [$aggregateRootId, $concurrencyCheck->lastEventId]);
        }

        if($concurrencyCheck instanceof WithLastKnownSequenceNumber){
            $finalSubQuery = $finalSubQuery->whereRaw("(SELECT `{$this->tableSchema->getSequenceColumn()}` FROM `$tableName` WHERE `{$this->tableSchema->getAggregateRootIdColumn()}` = ? ORDER BY `{$this->tableSchema->getSequenceColumn()}` DESC LIMIT 1) = ?", [$aggregateRootId, $concurrencyCheck->lastSequenceNumber]);
        }

        // Perform the insert using the final subquery
        $rowCount = $this->connection->table($tableName)->insertUsing($columns, $finalSubQuery);

        if($rowCount === 0){
            throw new ConcurrencyException();
        }
    }
}
