<?php

namespace Robertbaelde\Saucy\EventSourcing\EventSauce;

use EventSauce\EventSourcing\ClassNameInflector;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

final readonly class IlluminateEventStreamTableMigrator implements EventStreamTableMigrator
{
    public function __construct(
        private Connection $connection,
        private ClassNameInflector $aggregateRootNameInflector,
    )
    {
    }

    public function ensureTableExistsForAndReturnTableName(string $aggregateRoot): string
    {
        $type = $this->aggregateRootNameInflector->classNameToType($aggregateRoot);
        if($this->connection->getSchemaBuilder()->hasTable($type)){
            return $type;
        }

        $this->connection->getSchemaBuilder()->create($type, function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id');
            $table->string('aggregate_root_id');
            $table->integer('version');
            $table->json('payload');
            $table->index(['aggregate_root_id', 'version'], 'reconstitution_index');
            $table->unique(['aggregate_root_id', 'version'], 'idempotency_index');
        });

        return $type;
    }
}
