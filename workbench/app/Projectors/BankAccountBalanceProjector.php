<?php

namespace Workbench\App\Projectors;

use Illuminate\Database\Schema\Blueprint;
use Robertbaelde\Saucy\Attributes\Projector;
use Robertbaelde\Saucy\Attributes\QueryHandler;
use Robertbaelde\Saucy\EventSourcing\Projectors\IlluminateDatabaseProjector;
use Workbench\App\BankAccount;
use Workbench\App\Commands\BankAccountId;
use Workbench\App\Events\BankAccountCredited;
use Workbench\App\Query\BankAccountWithBalance;
use Workbench\App\Query\GetBankAccountBalance;
use Workbench\App\Query\GetBankAccountsWithBalance;

#[Projector(BankAccount::class)]
final class BankAccountBalanceProjector extends IlluminateDatabaseProjector
{
    const balanceColumn = 'balance';

    public function handleBankAccountCredited(BankAccountCredited $event): void
    {
        $bankAccount = $this->find();
        if($bankAccount === null){
            $this->create([self::balanceColumn => $event->amount]);
            return;
        }

        $this->update([self::balanceColumn => $bankAccount[self::balanceColumn] + $event->amount]);
    }

    #[QueryHandler]
    public function getBalance(GetBankAccountBalance $query): BankAccountWithBalance
    {
        $this->scopeAggregate($query->bankAccountId);
        $bankAccount = $this->queryBuilder->first();
        return new BankAccountWithBalance(BankAccountId::fromString($bankAccount->id), $bankAccount->balance);
    }

    #[QueryHandler]
    public function getBankAccountsWithBalance(GetBankAccountsWithBalance $query): array
    {
        return $this->queryBuilder->where(self::balanceColumn, '>=', $query->minBalance)->get()
            ->map(function($bankAccount){
                return new BankAccountWithBalance(BankAccountId::fromString($bankAccount->id), $bankAccount->balance);
            })
            ->toArray();
    }

    public function schema(Blueprint $blueprint): void
    {
        $blueprint->ulid($this->idColumnName())->primary();
        $blueprint->integer(self::balanceColumn);
    }
}
