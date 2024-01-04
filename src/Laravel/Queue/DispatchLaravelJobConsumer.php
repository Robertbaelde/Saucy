<?php

namespace Robertbaelde\Saucy\Laravel\Queue;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;

final readonly class DispatchLaravelJobConsumer implements MessageConsumer
{
    public function handle(Message $message): void
    {
        dispatch(new PassMessageToConsumers($message));
    }
}
