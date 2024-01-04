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
use Workbench\App\BankAccount;
use Workbench\App\Commands\BankAccountId;
use Workbench\App\Commands\CreditBankAccount;
use Workbench\App\Events\BankAccountCredited;
use Workbench\App\Projectors\BankAccountProjector;

final class EventSourcingFeatureTests extends WithDatabaseTestCase
{
    use WithWorkbench;

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
            amount: 300,
        ));


        // check if we can trigger projection

//        $messageRepository = resolve(EventSauceRepository::class)->getMessageRepositoryFor(BankAccount::class);
//
//        $sub = new ConsumerSubscription(
//            messageStream: new PerAggregateTypeStream(
//                messageRepository: $messageRepository,
//            ),
//            subscriptionState: resolve(IlluminateSubscriptionState::class),
//            consumer: new BankAccountProjector(),
//        );
//
//        $message = new Message(new BankAccountCredited(123), [
//            Header::AGGREGATE_ROOT_ID => $bankAccountId,
//            Header::AGGREGATE_ROOT_TYPE => 'bank_accounts',
//        ]);

//        $sub->trigger($message);

        // this should not yield to new results
//        $sub->trigger($message);

//        $message = new Message(new BankAccountCredited(123), [
//            Header::AGGREGATE_ROOT_ID => $bankAccountBId,
//        ]);
//        $sub->trigger($message);

//        $this->assertDatabaseCount('bank_accounts_events', 2);

        dd(DB::table('stream_positions')->get());


    }
}
