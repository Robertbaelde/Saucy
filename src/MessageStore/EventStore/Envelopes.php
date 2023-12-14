<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

final readonly class Envelopes
{
    /**
     * @var Envelope[]
     */
    public array $envelopes;

    public function __construct(
        public string|null $checkpoint,
        Envelope ...$envelopes,
    )
    {
        $this->envelopes = $envelopes;
    }

    public static function wrapEvents(array $events, ?Headers $headers = null): self
    {
        $envelopes = [];

        foreach ($events as $event) {
            $envelopes[] = new Envelope(
                $event,
                new Headers(
//                    new Header('eventId', (new EventIdGenerator())->generate()),
//                    new Header('type', get_class($event)),
                ),
            );
        }

        return new self(null, ...$envelopes);
    }

    public function events(): array
    {
        return array_map(function(Envelope $envelope){
            return $envelope->event;
        }, $this->envelopes);
    }
}
