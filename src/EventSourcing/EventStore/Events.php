<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;

final readonly class Events
{
    /**
     * @var Event[]
     */
    public array $events;

    public function __construct(Event ...$event)
    {
        $this->events = $event;
    }

    public static function of(Event ...$event): self
    {
        return new self(...$event);
    }

    public function asGeneratorForEventSauce(): \Generator
    {
        $sequence = 0;
        foreach ($this->events as $event) {
            yield $event->payload;
            $sequence = $event->headers->get(EventStoreHeader::EVENT_SEQUENCE);
        }
        return $sequence;
    }

    public function addHeader(EventStoreHeader | string $header, string $name): self
    {
        return new self(...array_map(function ($event) use ($header, $name) {
            return $event->addHeader($header, $name);
        }, $this->events));
    }

}
