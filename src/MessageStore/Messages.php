<?php

namespace Robertbaelde\Saucy\MessageStore;

final readonly class Messages
{
    /**
     * @var Message[]
     */
    public array $messages;

    public function __construct(Message ...$messages)
    {
        $this->messages = $messages;
    }

    public static function of(Message ...$messages): self
    {
        return new self(...$messages);
    }

    public function getCheckpoint(): string
    {
        return $this->messages[count($this->messages) - 1]->eventId;
    }

}
