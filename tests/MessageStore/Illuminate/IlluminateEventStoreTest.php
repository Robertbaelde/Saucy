<?php

namespace Robertbaelde\Saucy\Tests\MessageStore\Illuminate;

use Illuminate\Database\Capsule\Manager;
use PHPUnit\Framework\TestCase;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\StreamShouldNotExists;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\WithLastKnownEventId;
use Robertbaelde\Saucy\EventSourcing\EventStore\Event;
use Robertbaelde\Saucy\EventSourcing\EventStore\Events;
use Robertbaelde\Saucy\EventSourcing\EventStore\Exceptions\ConcurrencyException;
use Robertbaelde\Saucy\EventSourcing\EventStore\Exceptions\StreamExistsException;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\DefaultTableSchema;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\IlluminateEventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\PrefixedTableNameResolver;
use Robertbaelde\Saucy\EventSourcing\EventStore\Stream;

final class IlluminateEventStoreTest extends TestCase
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

        $this->connection->enableQueryLog();

        $this->eventStore()->migrate();
    }

    /** @test */
    public function it_can_append_a_message_to_the_stream(): void
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['bar' => 'baz'], []),
            )
        );

        $this->assertCount(1, $eventStore->getEvents(Stream::withName('stream-name'))->events);
    }

    /** @test */
    public function it_can_append_multiple_messages(): void
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['bar' => 'baz'], []),
                new Event('message-id-2', 'foo', ['bar' => 'baz'], []),
            ),
        );

        $this->assertCount(2, $eventStore->getEvents(Stream::withName('stream-name'))->events);
    }

    /** @test */
    public function it_can_append_messages_to_an_existing_stream(): void
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['bar' => 'baz'], []),
            ),
        );

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id-2', 'foo', ['bar' => 'baz'], []),
            ),
        );

        $this->assertCount(2, $eventStore->getEvents(Stream::withName('stream-name'))->events);

        $messages = $eventStore->getEvents(Stream::all())->events;
        $this->assertCount(2, $messages);
    }

    /** @test */
    public function it_doesnt_append_message_with_same_id()
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['bar' => 'baz'], []),
            ),
        );

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['bar' => 'baz'], []),
            ),
        );

        $this->assertCount(1, $eventStore->getEvents(Stream::withName('stream-name'))->events);

        $messages = $eventStore->getEvents(Stream::all())->events;
        $this->assertCount(1, $messages);
    }

    /** @test */
    public function it_can_do_constraint_check_that_stream_does_not_exists(): void
    {
        $eventStore = $this->eventStore();

        // should work
        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['bar' => 'baz'], []),
            ),
            new StreamShouldNotExists(),
        );

        $exceptionThrown = false;
        // should throw
        try {
            $eventStore->appendToStream(
                Stream::withName('stream-name'),
                Events::of(
                    new Event('message-id-2', 'foo', ['bar' => 'baz'], []),
                ),
                new StreamShouldNotExists(),
            );
        } catch (StreamExistsException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $messages = $eventStore->getEvents(Stream::withName('stream-name'))->events;

        $this->assertCount(1, $messages);
        $this->assertEquals('message-id', $messages[0]->eventId);

        $messages = $eventStore->getEvents(Stream::all())->events;
        $this->assertCount(1, $messages);
    }

    /** @test */
    public function it_can_do_last_known_event_id_concurrency_check()
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['f' => 'b'], []),
            ),
        );

        // should work
        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id-2', 'foo', ['f' => 'b'], []),
            ),
            new WithLastKnownEventId('message-id'),
        );

        // should work
        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id-3', 'foo', ['f' => 'b'], []),
                    new Event('message-id-4', 'foo', ['f' => 'b'], []),
            ),
            new WithLastKnownEventId('message-id-2'),
        );

        // should work
        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id-3', 'foo', ['f' => 'b'], []),
                new Event('message-id-5', 'foo', ['f' => 'b'], []),
            ),
            new WithLastKnownEventId('message-id-4'),
        );

        $exceptionThrown = false;
        // should throw
        try {
            $eventStore->appendToStream(
                Stream::withName('stream-name'),
                Events::of(
                    new Event('message-id-6', 'foo', ['f' => 'b']),
                ),
                new WithLastKnownEventId('message-id-4'),
            );
        } catch (ConcurrencyException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $messages = $eventStore->getEvents(Stream::withName('stream-name'))->events;
        $this->assertCount(5, $messages);

        $messages = $eventStore->getEvents(Stream::all())->events;
        $this->assertCount(5, $messages);
    }

    /** @test */
    public function events_are_appended_to_the_all_stream()
    {
        $eventStore = $this->eventStore();
        $eventStore->appendToStream(
            Stream::withName('stream-name'),
            Events::of(
                new Event('message-id', 'foo', ['f' => 'b'], []),
            ),
        );
        $eventStore->appendToStream(
            Stream::withName('stream-name-2'),
            Events::of(
                new Event('message-id-2', 'foo', ['f' => 'b'], []),
            ),
        );

        $messages = $eventStore->getEvents(Stream::all())->events;
        $this->assertCount(2, $messages);
    }

    private function eventStore(): IlluminateEventStore
    {
        return new IlluminateEventStore(
            connection: $this->connection,
            streamTableNameResolver: new PrefixedTableNameResolver('stream_'),
            tableSchema: new DefaultTableSchema(),
        );
    }
}
