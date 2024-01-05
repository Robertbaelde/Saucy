<?php

namespace Workbench\App\Query;

use Robertbaelde\Saucy\MessageBus\QueryBus\Query;
use Workbench\App\Commands\BankAccountId;

/** @implements Query<array<BankAccountWithBalance>> */
final readonly class GetBankAccountsWithBalance implements Query
{
    public function __construct(
        public int $minBalance,
    )
    {
    }

    public static function moreThan(int $minBalance): self
    {
        return new self($minBalance,);
    }
}
