<?php

namespace Robertbaelde\Saucy\Laravel\Queue;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\ConsumerDictionary;

final class PassMessageToConsumers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(
        public Message $message
    )
    {
    }

    public function handle(
        ConsumerDictionary $consumerDictionary,
    ): void
    {
        foreach ($consumerDictionary->getConsumersForAggregate($this->message->aggregateRootType()) as $consumer) {
            dispatch(new HandleConsumer(
                message: $this->message,
                aggregateRootClass: $this->message->aggregateRootType(),
                consumerName: $consumer->getName()
            ));
        }
    }
}
