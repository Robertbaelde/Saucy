<?php

namespace Robertbaelde\Saucy\Tests\Workbench;

use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use Illuminate\Support\Facades\DB;
use Robertbaelde\Saucy\MessageBus\QueryBus\QueryBus;
use Workbench\App\Commands\BankAccountId;
use Workbench\App\Events\BankAccountCredited;
use Workbench\App\Projectors\BankAccountBalanceProjector;
use Workbench\App\Query\GetBankAccountBalance;
use Workbench\App\Query\GetBankAccountsWithBalance;

final class ProjectorsFeatureTest extends WithDatabaseTestCase
{
    /** @test */
    public function it_can_do_upserts()
    {
        $id = BankAccountId::generate();
        $id2 = BankAccountId::generate();
        $id3 = BankAccountId::generate();

        $projector = resolve(BankAccountBalanceProjector::class);

        $projector->handle(new Message(new BankAccountCredited(50), [
            Header::AGGREGATE_ROOT_ID => $id,
            Header::AGGREGATE_ROOT_TYPE => 'bank_accounts',
        ]));
        $projector->handle(new Message(new BankAccountCredited(150), [
            Header::AGGREGATE_ROOT_ID => $id,
            Header::AGGREGATE_ROOT_TYPE => 'bank_accounts',
        ]));

        $projector->handle(new Message(new BankAccountCredited(5), [
            Header::AGGREGATE_ROOT_ID => $id2,
            Header::AGGREGATE_ROOT_TYPE => 'bank_accounts',
        ]));

        $projector->handle(new Message(new BankAccountCredited(500), [
            Header::AGGREGATE_ROOT_ID => $id3,
            Header::AGGREGATE_ROOT_TYPE => 'bank_accounts',
        ]));


        // execute query
        /** @var QueryBus $queryBus */
        $queryBus = resolve(QueryBus::class);
        $bankAccountBalance = $queryBus->handle(new GetBankAccountBalance($id));
        $this->assertEquals(200, $bankAccountBalance->balance);

        $bankAccounts = $queryBus->handle(GetBankAccountsWithBalance::moreThan(10));
        $this->assertCount(2, $bankAccounts);

//        foreach ($bankAccounts as $bankAccount) {
//            $this->assertGreaterThan(10, $bankAccount->balance);
//        }
//        dd(DB::table($projector->tableName())->get());
        // assert that..
    }
}
