<?php

namespace Workbench\App\Query;

use Robertbaelde\Saucy\MessageBus\QueryBus\Query;
use Workbench\App\Commands\BankAccountId;

/** @implements Query<BankAccountWithBalance> */
final readonly class GetBankAccountBalance implements Query
{
    public function __construct(
        public BankAccountId $bankAccountId,
    )
    {
    }
}
