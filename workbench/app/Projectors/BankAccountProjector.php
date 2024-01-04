<?php

namespace Workbench\App\Projectors;

use EventSauce\EventSourcing\EventConsumption\EventConsumer;
use EventSauce\EventSourcing\EventConsumption\HandleMethodInflector;
use EventSauce\EventSourcing\EventConsumption\InflectHandlerMethodsFromType;
use EventSauce\EventSourcing\Message;
use Robertbaelde\Saucy\Attributes\Projector;
use Workbench\App\BankAccount;
use Workbench\App\Events\BankAccountCredited;

#[Projector(BankAccount::class)]
final class BankAccountProjector extends EventConsumer
{
    public function handleBankAccountCredited(BankAccountCredited $event, Message $message): void
    {
        dump($event);
    }

    protected function handleMethodInflector(): HandleMethodInflector
    {
        return new InflectHandlerMethodsFromType();
    }
}
