<?php

namespace MessageStore\Illuminate;

use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Serialization\ConstructingPayloadSerializer;
use Illuminate\Database\Capsule\Manager;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\DefaultTableSchema;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\IlluminateEventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\PrefixedTableNameResolver;
use Robertbaelde\Saucy\EventSourcing\EventStore\Serialization\EventSerializer;
use Robertbaelde\Saucy\Tests\MessageStore\Illuminate\AbstractAggregateEventStoreTest;
use Robertbaelde\Saucy\Tests\MessageStore\Illuminate\AbstractEventStoreTest;

final class IlluminateAggregateEventStoreTest extends AbstractAggregateEventStoreTest
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

    protected function eventStore(): IlluminateEventStore
    {
        return new IlluminateEventStore(
            connection: $this->connection,
            streamTableNameResolver: new PrefixedTableNameResolver('stream_'),
            tableSchema: new DefaultTableSchema(),
            eventSerializer: new EventSerializer(
                new ConstructingPayloadSerializer(),
                new DotSeparatedSnakeCaseInflector(),
            ),
        );
    }
}
