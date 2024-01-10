<?php

namespace Robertbaelde\Saucy\Tests\Workbench;

use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\IdEncoding\StringIdEncoder;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateMessageRepository;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Robertbaelde\Saucy\EventSourcing\EventSauce\EventSauceRepository;
use Robertbaelde\Saucy\EventSourcing\EventSauce\EventStreamTableMigrator;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\ConsumerSubscription;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\Repositories\IlluminateSubscriptionState;
use Robertbaelde\Saucy\EventSourcing\Streams\PerAggregateRootInstanceStream;
use Robertbaelde\Saucy\EventSourcing\Streams\PerAggregateTypeStream;
use Robertbaelde\Saucy\MessageBus\CommandBus\CommandBus;
use Robertbaelde\Saucy\MessageBus\QueryBus\QueryBus;
use Workbench\App\BankAccount;
use Workbench\App\Commands\BankAccountId;
use Workbench\App\Commands\CreditBankAccount;
use Workbench\App\Events\BankAccountCredited;
use Workbench\App\Projectors\BankAccountBalanceProjector;
use Workbench\App\Query\BankAccountWithBalance;
use Workbench\App\Query\GetBankAccountBalance;

final class EventSourcingFeatureTest extends WithDatabaseTestCase
{

    /** @test */
    public function it_handles_commands_and_queries()
    {
        $bankAccountId = BankAccountId::generate();
        $bankAccountBId = BankAccountId::generate();

        $commandBus = $this->app->make(CommandBus::class);
        $commandBus->handle(new CreditBankAccount(
            bankAccountId: $bankAccountId,
            amount: 100,
        ));
        $commandBus->handle(new CreditBankAccount(
            bankAccountId: $bankAccountId,
            amount: 200,
        ));

        $commandBus->handle(new CreditBankAccount(
            bankAccountId: $bankAccountBId,
            amount: 150,
        ));

        $queryBus = $this->app->make(QueryBus::class);

        /** @var BankAccountWithBalance $bankAccountWithBalance */
        $bankAccountWithBalance = $queryBus->query(new GetBankAccountBalance($bankAccountId));
        $this->assertTrue($bankAccountWithBalance->bankAccountId->equals($bankAccountId));
        $this->assertEquals(300, $bankAccountWithBalance->balance);

        /** @var BankAccountWithBalance $bankAccountWithBalance */
        $bankAccountWithBalance = $queryBus->query(new GetBankAccountBalance($bankAccountBId));
        $this->assertTrue($bankAccountWithBalance->bankAccountId->equals($bankAccountBId));
        $this->assertEquals(150, $bankAccountWithBalance->balance);
    }
}
