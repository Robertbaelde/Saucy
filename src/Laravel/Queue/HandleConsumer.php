<?php

namespace Robertbaelde\Saucy\Laravel\Queue;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Robertbaelde\Saucy\EventSourcing\CommandHandler\AggregateRootDirectory;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\ConsumerDictionary;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\Repositories\CouldNotGetConsumerLock;

final class HandleConsumer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Message $message,
        public string $aggregateRootClass,
        public string $consumerName,
    )
    {
    }

    public function handle(
        ConsumerDictionary $consumerDictionary,
    ): void
    {
        $consumer = $consumerDictionary->getConsumerForAggregateByName(
            $this->aggregateRootClass,
            $this->consumerName
        );
        $consumerIdentifier = $consumer->getIdentifier($this->message);

        try {
            $consumerDictionary->getConsumerForAggregateByName(
                $this->aggregateRootClass,
                $this->consumerName
            )->trigger($consumerIdentifier);
        } catch (CouldNotGetConsumerLock $lock){
            return;
        }

        if($consumer->shouldProcessMoreMessages($consumerIdentifier)){
            dispatch(new HandleConsumer(
                message: $this->message,
                aggregateRootClass: $this->aggregateRootClass,
                consumerName: $this->consumerName
            ));
        } else {
            Cache::forget($consumerIdentifier);
        }

    }
}
