<?php

namespace Robertbaelde\Saucy\EventSourcing\EventSauce;


interface EventStreamTableMigrator
{
    public function ensureTableExistsForAndReturnTableName(string $aggregateRoot): string;
}
