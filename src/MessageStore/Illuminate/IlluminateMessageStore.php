<?php

namespace Robertbaelde\Saucy\MessageStore\Illuminate;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\ConcurrencyCheck;
use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\StreamShouldNotExists;
use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\WithLastKnownEventId;
use Robertbaelde\Saucy\MessageStore\Message;
use Robertbaelde\Saucy\MessageStore\MessageStore;
use Robertbaelde\Saucy\MessageStore\Exceptions\ConcurrencyException;
use Robertbaelde\Saucy\MessageStore\Exceptions\StreamExistsException;
use Robertbaelde\Saucy\MessageStore\Messages;
use Robertbaelde\Saucy\MessageStore\Position;
use Robertbaelde\Saucy\MessageStore\Stream;

final readonly class IlluminateMessageStore implements MessageStore
{
    public function __construct(
        private ConnectionInterface $connection,
        private StreamTableNameResolver $streamTableNameResolver,
        private TableSchema $tableSchema,
    )
    {
    }

    /**
     * @throws ConcurrencyException
     * @throws \Exception
     */
    public function appendToStream(Stream $stream, Messages $messages, ?ConcurrencyCheck $concurrencyCheck = null): void
    {
        $tableName = $this->streamTableNameResolver->streamToTableName($stream);

        $insertValues = [];
        foreach ($messages->messages as $message){
            $insertValues[] = [
                $this->tableSchema->getEventIdColumn() => $message->eventId,
                $this->tableSchema->getTypeColumn() => $message->type,
                $this->tableSchema->getPayloadColumn() => json_encode($message->payload),
                $this->tableSchema->getMetaDataColumn() => json_encode($message->metaData),
            ];
        }

        if($concurrencyCheck instanceof StreamShouldNotExists){
            if($this->streamExists($tableName) && !$this->checkStreamEmpty($tableName)){
                throw new StreamExistsException();
            }
        }

        try {
            $this->connection->beginTransaction();
            $this->insert($tableName, $insertValues, $concurrencyCheck);
            $this->insertIntoAll($insertValues, $stream->name);
            $this->connection->commit();
        } catch (QueryException $queryException) {
            $this->connection->rollBack();

            if($queryException->getCode() !== '42S02'){
                throw $queryException;
            }

            if(str_contains($queryException->getMessage(), $this->streamTableNameResolver->streamToTableName(Stream::all()))){
                throw new \Exception('all stream does not exist');
            }

            $this->createStreamTable($tableName);
            $this->appendToStream($stream, $messages, $concurrencyCheck);

        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }

    public function getMessages(Stream $stream, ?Position $position = null, ?int $limit = null): Messages
    {
        try {
            return new Messages(...array_map(function (object $row){
                return new Message(
                    eventId: $row->{$this->tableSchema->getEventIdColumn()},
                    type: $row->{$this->tableSchema->getTypeColumn()},
                    payload: json_decode($row->{$this->tableSchema->getPayloadColumn()}, true),
                    metaData: json_decode($row->{$this->tableSchema->getMetaDataColumn()}, true),
                );
            }, $this->connection->table($this->streamTableNameResolver->streamToTableName($stream))->orderBy($this->tableSchema->getIdColumn())->get()->toArray()));
        } catch (QueryException $queryException) {
            if($queryException->getCode() !== '42S02'){
                throw $queryException;
            }
            return new Messages();
        }
    }

    private function createStreamTable(string $tableName): void
    {
        if(!$this->connection instanceof Connection){
            throw new \Exception('Connection is not an instance of Connection');
        }
        $this->connection->getSchemaBuilder()->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string($this->tableSchema->getEventIdColumn());
            $table->string($this->tableSchema->getTypeColumn());
            $table->json($this->tableSchema->getPayloadColumn());
            $table->json($this->tableSchema->getMetaDataColumn());
            $table->unique($this->tableSchema->getEventIdColumn(), 'event_id_unique');
        });
    }

    private function streamExists(string $tableName): bool
    {
        return $this->connection->getSchemaBuilder()->hasTable($tableName);
    }

    private function checkStreamEmpty(string $tableName): bool
    {
        return $this->connection->table($tableName)->count() === 0;
    }

    private function insert(string $tableName, array $insertValues, ?ConcurrencyCheck $concurrencyCheck): void
    {

        if(!$concurrencyCheck instanceof WithLastKnownEventId){
            $this->connection->table($tableName)->insertOrIgnore($insertValues);
            return;
        }

        $columns = array_keys($insertValues[0]);

        // Constructing a union subquery for each row
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
        $finalSubQuery = $this->connection->table($this->connection->raw("($subQuerySql) as sub"))
            ->whereRaw("(SELECT `{$this->tableSchema->getEventIdColumn()}` FROM `$tableName` ORDER BY `{$this->tableSchema->getIdColumn()}` DESC LIMIT 1) = ?", [$concurrencyCheck->lastEventId]);

        // Perform the insert using the final subquery
        $rowCount = $this->connection->table($tableName)->insertUsing($columns, $finalSubQuery);

        if($rowCount === 0){
            throw new ConcurrencyException();
        }
    }

    private function insertIntoAll(array $insertValues, string $originalStream): void
    {
        $insertValues = array_map(function ($row) use ($originalStream){
            $row[$this->tableSchema->getMetaDataColumn()] = json_encode(array_merge(json_decode($row[$this->tableSchema->getMetaDataColumn()], true), ['_original_stream' => $originalStream]));
            return $row;
        }, $insertValues);

        $this->connection->table($this->streamTableNameResolver->streamToTableName(Stream::all()))->insertOrIgnore($insertValues);
    }

    public function migrate(): void
    {
        $this->createStreamTable($this->streamTableNameResolver->streamToTableName(Stream::all()));
    }
}
