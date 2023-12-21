<?php

namespace Robertbaelde\Saucy\Tests\MessageStore\Illuminate;

use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Serialization\ConstructingPayloadSerializer;
use Illuminate\Database\Capsule\Manager;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\DefaultTableSchema;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\PrefixedTableNameResolver;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\StreamPerTypeIlluminateEventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\StreamPerTypeTableSchema;
use Robertbaelde\Saucy\EventSourcing\EventStore\Serialization\EventSerializer;

final class StreamPerTypeIlluminateEventStoreTest extends AbstractAggregateEventStoreTest
{
    private \Illuminate\Database\Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = new Manager;
        $manager->addConnection(
            [
                'driver' => 'mysql',
                'host' => getenv('SAUCY_TESTING_MYSQL_HOST') ?: '127.0.0.1',
                'port' => getenv('SAUCY_TESTING_MYSQL_PORT') ?: '3306',
                'database' => 'event_store',
                'username' => 'root',
                'password' => 'password',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        );

        $this->connection = $manager->getConnection();
        $this->connection->getSchemaBuilder()->dropAllTables();
    }

    protected function eventStore(): StreamPerTypeIlluminateEventStore
    {
        return new StreamPerTypeIlluminateEventStore(
            connection: $this->connection,
            streamTableNameResolver: new PrefixedTableNameResolver(''),
            tableSchema: new StreamPerTypeTableSchema(),
            eventSerializer: new EventSerializer(
                new ConstructingPayloadSerializer(),
                new DotSeparatedSnakeCaseInflector(),
            ),
        );
    }
}
