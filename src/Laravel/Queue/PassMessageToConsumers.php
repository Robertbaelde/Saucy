<?php

namespace Robertbaelde\Saucy\Laravel\Queue;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
            $consumerIdentifier = $consumer->getIdentifier($this->message);
            if(config('queue.default') === 'sync'){
                // prevent weird issues in testing..
                $consumer->trigger($consumerIdentifier);
                continue;
            }

            if(Cache::has($consumerIdentifier)){
                Cache::increment($consumerIdentifier);
            } else {
                dispatch(new HandleConsumer(
                    message: $this->message,
                    aggregateRootClass: $this->message->aggregateRootType(),
                    consumerName: $consumer->getName()
                ));
                Cache::put($consumerIdentifier, 1, 60);
            }

        }
    }
}
