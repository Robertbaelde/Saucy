<?php

namespace Robertbaelde\Saucy\Tests\MessageStore\Illuminate;

use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Serialization\ConstructingPayloadSerializer;
use Illuminate\Database\Capsule\Manager;
use PHPUnit\Framework\TestCase;
use Robertbaelde\Saucy\EventSourcing\EventStore\AggregateStream;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\StreamShouldNotExists;
use Robertbaelde\Saucy\EventSourcing\EventStore\ConcurrencyChecks\WithLastKnownEventId;
use Robertbaelde\Saucy\EventSourcing\EventStore\Event;
use Robertbaelde\Saucy\EventSourcing\EventStore\Events;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\Exceptions\ConcurrencyException;
use Robertbaelde\Saucy\EventSourcing\EventStore\Exceptions\StreamExistsException;
use Robertbaelde\Saucy\EventSourcing\EventStore\Headers;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\DefaultTableSchema;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\IlluminateEventStore;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\PrefixedTableNameResolver;
use Robertbaelde\Saucy\EventSourcing\EventStore\Serialization\EventSerializer;
use Robertbaelde\Saucy\EventSourcing\EventStore\NamedStream;
use Robertbaelde\Saucy\Tests\stubs\TestEvent;

abstract class AbstractAggregateEventStoreTest extends TestCase
{
    /** @test */
    public function it_can_append_a_message_to_the_stream(): void
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            )
        );

        $this->assertCount(1, $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events);
        $this->assertCount(0, $eventStore->getEvents(AggregateStream::for('order', 'order-id-2'))->events);
    }

    /** @test */
    public function it_can_append_multiple_messages(): void
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
                $this->getEventWithId('message-id-2'),
            ),
        );

        $this->assertCount(2, $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events);

    }

    /** @test */
    public function it_can_append_messages_to_an_existing_stream(): void
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            ),
        );

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-2'),
            ),
        );

        $this->assertCount(2, $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events);
    }

    /** @test */
    public function it_doesnt_append_message_with_same_id()
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            ),
        );

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            ),
        );

        $this->assertCount(1, $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events);
    }

    /** @test */
    public function it_can_do_constraint_check_that_stream_does_not_exists(): void
    {
        $eventStore = $this->eventStore();

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            ),
            new StreamShouldNotExists(),
        );

        // should work as well
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id-2'),
            Events::of(
                $this->getEventWithId('message-id-1'),
            ),
            new StreamShouldNotExists(),
        );

        $exceptionThrown = false;
        // should throw
        try {
            $eventStore->appendToStream(
                AggregateStream::for('order', 'order-id'),
                Events::of(
                    $this->getEventWithId('message-id'),
                ),
                new StreamShouldNotExists(),
            );
        } catch (StreamExistsException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $messages = $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events;

        $this->assertCount(1, $messages);
        $this->assertEquals('message-id', $messages[0]->eventId);
    }

    /** @test */
    public function it_can_do_last_known_event_id_concurrency_check()
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            ),
        );

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-2'),
            ),
            new WithLastKnownEventId('message-id'),
        );

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-3'),
                $this->getEventWithId('message-id-4'),
            ),
            new WithLastKnownEventId('message-id-2'),
        );

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-3'),
                $this->getEventWithId('message-id-5'),
            ),
            new WithLastKnownEventId('message-id-4'),
        );

        $exceptionThrown = false;
        // should throw
        try {
            $eventStore->appendToStream(
                AggregateStream::for('order', 'order-id'),
                Events::of(
                    $this->getEventWithId('message-id-6'),
                ),
                new WithLastKnownEventId('message-id-4'),
            );
        } catch (ConcurrencyException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $messages = $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events;
        $this->assertCount(5, $messages);

    }

    /** @test */
    public function it_can_do_last_known_event_id_concurrency_check_ignoring_other_aggregate_ids()
    {
        $eventStore = $this->eventStore();

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id'),
            ),
        );

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id-2'),
            Events::of(
                $this->getEventWithId('o2-message-id'),
            ),
        );

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-2'),
            ),
            new WithLastKnownEventId('message-id'),
        );

        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id-2'),
            Events::of(
                $this->getEventWithId('o2-message-id-2'),
            ),
        );

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-3'),
                $this->getEventWithId('message-id-4'),
            ),
            new WithLastKnownEventId('message-id-2'),
        );

        // should work
        $eventStore->appendToStream(
            AggregateStream::for('order', 'order-id'),
            Events::of(
                $this->getEventWithId('message-id-3'),
                $this->getEventWithId('message-id-5'),
            ),
            new WithLastKnownEventId('message-id-4'),
        );

        $exceptionThrown = false;
        // should throw
        try {
            $eventStore->appendToStream(
                AggregateStream::for('order', 'order-id'),
                Events::of(
                    $this->getEventWithId('message-id-6'),
                ),
                new WithLastKnownEventId('message-id-4'),
            );
        } catch (ConcurrencyException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $messages = $eventStore->getEvents(AggregateStream::for('order', 'order-id'))->events;
        $this->assertCount(5, $messages);

    }

    protected abstract function eventStore(): EventStore;

    private function getEventWithId(string $id): Event
    {
        return new Event($id, new TestEvent(), new Headers(['bar' => 'baz']));
    }
}
