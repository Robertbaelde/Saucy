<?php

namespace Workbench\App\Commands;

use Robertbaelde\Saucy\Attributes\AggregateRoot;

final readonly class CreditBankAccount
{
    public function __construct(
        public BankAccountId $bankAccountId,
        public int $amount,
    )
    {
    }
}
