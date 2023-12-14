<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore\Serialization;

use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use Robertbaelde\Saucy\EventSourcing\EventStore\Event;
use Robertbaelde\Saucy\EventSourcing\EventStore\Events;
use Robertbaelde\Saucy\EventSourcing\EventStore\EventStoreHeader;
use Robertbaelde\Saucy\EventSourcing\EventStore\Headers;
use Robertbaelde\Saucy\EventSourcing\EventStore\Illuminate\TableSchema;

final readonly class EventSerializer
{
    public function __construct(
        private PayloadSerializer $payloadSerializer,
        private ClassNameInflector $classNameInflector,
    )
    {
    }

    public function serializeEvents(Events $events, TableSchema $tableSchema): array
    {
        $insertValues = [];
        foreach ($events->events as $event){
            $insertValues[] = [
                $tableSchema->getEventIdColumn() => $event->eventId,
                $tableSchema->getTypeColumn() => $this->classNameInflector->instanceToType($event->payload),
                $tableSchema->getPayloadColumn() => json_encode($this->payloadSerializer->serializePayload($event->payload)),
                $tableSchema->getHeadersColumn() => json_encode($event->headers->toArray()),
            ];
        }

        return $insertValues;
    }

    public function deserializeEvents(array $data, TableSchema $tableSchema): Events
    {
        $events = [];
        foreach ($data as $row){
            $events[] = new Event(
                eventId: $row->{$tableSchema->getEventIdColumn()},
                payload: $this->payloadSerializer->unserializePayload(
                    className: $this->classNameInflector->typeToClassName($row->{$tableSchema->getTypeColumn()}),
                    payload: json_decode($row->{$tableSchema->getPayloadColumn()}, true),
                ),
                headers: Headers::fromArray(json_decode($row->{$tableSchema->getHeadersColumn()}, true))
                ->with(EventStoreHeader::EVENT_SEQUENCE, $row->{$tableSchema->getSequenceColumn()})
            );
        }
        return new Events(...$events);
    }
}
