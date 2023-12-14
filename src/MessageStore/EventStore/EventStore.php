<?php

namespace Robertbaelde\Saucy\MessageStore\EventStore;

use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\ConcurrencyCheck;
use Robertbaelde\Saucy\MessageStore\Message;
use Robertbaelde\Saucy\MessageStore\Messages;
use Robertbaelde\Saucy\MessageStore\MessageStore;
use Robertbaelde\Saucy\MessageStore\Position;
use Robertbaelde\Saucy\MessageStore\Stream;

final readonly class EventStore
{
    public function __construct(
        private MessageStore $messageStore,
        private PayloadSerializer $payloadSerializer,
        private ClassNameInflector $classNameInflector,
        private EventIdGenerator $eventIdGenerator,
    )
    {
    }

    public function appendToStream(Stream $stream, Envelopes $envelopes, ?ConcurrencyCheck $concurrencyCheck = null): void
    {
        $messages = new Messages(...array_map(function(Envelope $envelope){
                    return new Message(
                        eventId: $this->eventIdGenerator->generate(),
                        type: $this->classNameInflector->instanceToType($envelope->event),
                        payload: $this->payloadSerializer->serializePayload($envelope->event),
                        metaData: $envelope->headers->toArray(),
                    );
            }, $envelopes->envelopes)
        );
        $this->messageStore->appendToStream($stream, $messages, $concurrencyCheck);
    }

    public function getEvents(Stream $stream, ?Position $position= null, ?int $limit = null): Envelopes
    {
        $messages = $this->messageStore->getMessages($stream, $position, $limit);
        $checkpoint = $messages->getCheckpoint();
        return (new Envelopes($checkpoint, ...array_map(function(Message $message){
            return new Envelope(
                event: $this->payloadSerializer->unserializePayload(
                    className: $this->classNameInflector->typeToClassName($message->type),
                    payload: $message->payload
                ),
                headers: Headers::fromArray($message->metaData),
            );
        }, $messages->messages)));
    }


}
