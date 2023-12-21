<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Container\ContainerInterface;
use Robertbaelde\Saucy\MessageBus\CommandBus\Handler;

final class ProcessCommand implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(
        public Handler $handler,
        public object $message,
    )
    {
    }

    public function handle(ContainerInterface $container): void
    {
        $this->handler->isStatic
            ? $this->handler->containerIdentifier::{$this->handler->methodName}($this->message)
            : $container->get($this->handler->containerIdentifier)->{$this->handler->methodName}($this->message);
    }
}
