<?php

namespace Workbench\App\Query;

use Workbench\App\Commands\BankAccountId;

final readonly class BankAccountWithBalance
{
    public function __construct(
        public BankAccountId $bankAccountId,
        public int $balance,
    )
    {
    }
}
