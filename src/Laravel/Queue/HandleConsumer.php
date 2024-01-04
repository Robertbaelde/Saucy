<?php

namespace Robertbaelde\Saucy\Laravel\Queue;

use EventSauce\EventSourcing\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Robertbaelde\Saucy\EventSourcing\CommandHandler\AggregateRootDirectory;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\ConsumerDictionary;

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
        $consumerDictionary->getConsumerForAggregateByName(
            $this->aggregateRootClass,
            $this->consumerName
        )->trigger($this->message);
    }
}
