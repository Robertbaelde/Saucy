<?php

namespace Workbench\App;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use Robertbaelde\Saucy\Attributes\CommandHandler;
use Workbench\App\Commands\BankAccountId;
use Workbench\App\Commands\CreditBankAccount;
use Workbench\App\Events\BankAccountCredited;

#[\Robertbaelde\Saucy\Attributes\AggregateRoot(BankAccountId::class, 'bank_accounts')]
final class BankAccount implements AggregateRoot
{
    use AggregateRootBehaviour;

    private int $balance = 0;

    #[CommandHandler]
    public function credit(CreditBankAccount $creditBankAccount): void
    {
        if ($creditBankAccount->amount <= 0) {
            throw new \InvalidArgumentException('Can only credit a positive amount.');
        }

        $this->recordThat(new BankAccountCredited($creditBankAccount->amount));
    }

    private function applyBankAccountCredited(BankAccountCredited $event): void
    {
        $this->balance += $event->amount;
    }
}
