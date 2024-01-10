<?php

namespace Robertbaelde\Saucy\EventSourcing\Projectors;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\EventConsumption\InflectHandlerMethodsFromType;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Workbench\App\Commands\BankAccountId;

abstract class IlluminateDatabaseProjector implements MessageConsumer
{
    protected Builder $queryBuilder;
    private AggregateRootId $scopedAggregateRootId;

    public function __construct(private Connection $connection)
    {
        $this->queryBuilder = $this->connection->table($this->tableName());
    }

    protected function upsert(array $array): void
    {
        if($this->queryBuilder->exists()){
            $this->update($array);
            return;
        }
        $this->create($array);
    }

    protected function update(array $array): void
    {
        $this->queryBuilder->clone()->update($array);
    }
    protected function increment(string $column, int $amount = 1): void
    {
        $this->queryBuilder->clone()->increment($column, $amount);
    }

    protected function create(array $array): void
    {
        $this->queryBuilder->clone()->insert(array_merge($array, [
            $this->idColumnName() => $this->scopedAggregateRootId->toString(),
        ]));
    }

    protected function find(): ?array
    {
        $row = $this->queryBuilder->clone()->first();
        if($row === null){
            return null;
        }
        return get_object_vars($row);
    }

    protected function delete(): void
    {
        $this->queryBuilder->clone()->delete();
    }

    public function tableName(): string
    {
        return 'projection_' . Str::of(get_class($this))->afterLast('\\')->snake();
    }

    abstract function schema(Blueprint $blueprint): void;

    public function idColumnName(): string
    {
        return 'id';
    }

    public function scopeAggregate(AggregateRootId $aggregateRootId): void
    {
        $this->scopedAggregateRootId = $aggregateRootId;
        $this->queryBuilder = $this->queryBuilder->where($this->idColumnName(), $aggregateRootId->toString());
    }
    public function handle(Message $message): void
    {
        $this->queryBuilder = $this->connection->table($this->tableName());
        $this->scopeAggregate($message->aggregateRootId());

       $this->migrate();

        $methods = (new InflectHandlerMethodsFromType())->handleMethods($this, $message);
        foreach ($methods as $method) {
            if (method_exists($this, $method)) {
                $this->{$method}($message->payload(), $message->aggregateRootId(), $message);
            }
        }
    }

    protected function migrate()
    {
        $this->connection->getSchemaBuilder()->hasTable($this->tableName()) || $this->connection->getSchemaBuilder()->create($this->tableName(), function (Blueprint $blueprint) {
            $this->schema($blueprint);
        });
    }

    public function reset()
    {
        $this->connection->getSchemaBuilder()->dropIfExists($this->tableName());
    }
}
