<?php

namespace Robertbaelde\Saucy;

use Domain\AccessControl\Application\TicketSync\BitscanLegacyTicketSyncReactor;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\ConstructingPayloadSerializer;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use League\ConstructFinder\ConstructFinder;
use Robertbaelde\AttributeFinder\AttributeFinder;
use Robertbaelde\AttributeFinder\ClassAttribute;
use Robertbaelde\Saucy\Attributes\AggregateRoot;
use Robertbaelde\Saucy\Attributes\Projector;
use Robertbaelde\Saucy\ClassNameMapper\AttributeClassNameInflector;
use Robertbaelde\Saucy\EventSourcing\CommandHandler\AggregateRootDirectory;
use Robertbaelde\Saucy\EventSourcing\CommandHandler\EventSourcingCommandHandlerMiddleware;
use Robertbaelde\Saucy\EventSourcing\EventSauce\EventSauceRepository;
use Robertbaelde\Saucy\EventSourcing\EventSauce\IlluminateEventStreamTableMigrator;
use Robertbaelde\Saucy\EventSourcing\EventSauce\SuffixClassNameInflector;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\ConsumerDictionary;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\ConsumerSubscription;
use Robertbaelde\Saucy\EventSourcing\MessageConsumption\Repositories\IlluminateSubscriptionState;
use Robertbaelde\Saucy\EventSourcing\Streams\PerAggregateTypeStream;
use Robertbaelde\Saucy\Laravel\Queue\DispatchLaravelJobConsumer;
use Robertbaelde\Saucy\MessageBus\CommandBus\AnnotationClassMapGenerator;
use Robertbaelde\Saucy\MessageBus\CommandBus\CommandBus;
use Robertbaelde\Saucy\MessageBus\CommandBus\CommandHandlerMiddleware;
use Robertbaelde\Saucy\MessageBus\CommandBus\MappedCommandHandlerLocator;
use Robertbaelde\Saucy\MessageBus\QueryBus\MappedQueryHandlerLocator;
use Robertbaelde\Saucy\MessageBus\QueryBus\QueryBus;
use Robertbaelde\Saucy\MessageBus\QueryBus\QueryHandlerLocation;
use Robertbaelde\Saucy\MessageBus\QueryBus\QueryHandlerMiddleware;
use Workbench\App\BankAccount;
use Workbench\App\Projectors\BankAccountBalanceProjector;
use Workbench\App\Projectors\BankAccountProjector;
use Workbench\App\Query\GetBankAccountBalance;

final class SaucyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/Laravel/database/migrations');
//        $this->publishes([
//            __DIR__.'/../config/saucy.php' => config_path('saucy.php'),
//        ], 'config');
    }

    public function register()
    {

//        $this->mergeConfigFrom(__DIR__.'/../config/saucy.php', 'saucy');

        $classes = ConstructFinder::locatedIn(__DIR__ . '/../workbench/app')->findClassNames(); // todo, move to config

        // todo, cleanup

        $aggregates = [];
        foreach (AttributeFinder::inClasses($classes)->withName(AggregateRoot::class)->findClassAttributes() as $classAttribute){
            $aggregates[$classAttribute->class] = $classAttribute->attribute->name !== null ? [$classAttribute->attribute->name, $classAttribute->class] : $classAttribute->class;
        }

        $mapped = [];
        foreach ($aggregates as $class => $type){
            if(is_array($type)){
                $mapped[$type[0]] = $type[1];
            }
            else {
                $mapped[$type] = $class;
            }
        }

        $aggregateRootDirectory = new AggregateRootDirectory($mapped);
        $this->app->instance(AggregateRootDirectory::class, $aggregateRootDirectory);

        $aggregateRootClassNameInflector = new ExplicitlyMappedClassNameInflector($aggregates);

        $attributeClassNameInflector = AttributeClassNameInflector::create($classes);

        $eventSerializer = new ConstructingMessageSerializer(
            $attributeClassNameInflector,
            new ConstructingPayloadSerializer(),
        );

        // event recorded -> queue -> event handler -> projection engine
        $connection = DB::connection();

        $eventSauceRepository = new EventSauceRepository(
            $connection,
            $aggregateRootClassNameInflector,
            new IlluminateEventStreamTableMigrator($connection, new SuffixClassNameInflector($aggregateRootClassNameInflector, '_events')),
            $eventSerializer,
            new DefaultHeadersDecorator(
                inflector: $attributeClassNameInflector,
            ),
            new SynchronousMessageDispatcher(new DispatchLaravelJobConsumer()),
        );

        $this->app->instance(EventSauceRepository::class, $eventSauceRepository);

        // register event consumers

        $projectors = AttributeFinder::inClasses($classes)->withName(Projector::class)->findClassAttributes();

        $consumerDictionary = new ConsumerDictionary();
        foreach ($projectors as $projector){
            $attribute = $projector->attribute;
            if(!$attribute instanceof Projector){
                continue;
            }
            $consumerDictionary->register($aggregateRootDirectory->getAggregateRootName($attribute->aggregateRootClass), new ConsumerSubscription(
                messageStream: new PerAggregateTypeStream(
                    messageRepository: $eventSauceRepository->getMessageRepositoryFor($attribute->aggregateRootClass),
                ),
                subscriptionState: $this->app->make(IlluminateSubscriptionState::class),
                consumer: $this->app->make($projector->class),
            ));
        }

        $this->app->instance(ConsumerDictionary::class, $consumerDictionary);

        $locator = new MappedCommandHandlerLocator(
            (new AnnotationClassMapGenerator($classes))->getMap(),
        );



        $this->app->instance(CommandBus::class,
            new CommandBus(
                new EventSourcingCommandHandlerMiddleware(
                    $locator,
                    $aggregateRootDirectory,
                    $this->app,
                ),
                new CommandHandlerMiddleware(
                    $this->app,
                    $locator,
                )
            )
        );

        $this->app->instance(QueryBus::class,
            new QueryBus(
                new QueryHandlerMiddleware(
                    $this->app,
                    new MappedQueryHandlerLocator(
                        (new \Robertbaelde\Saucy\MessageBus\QueryBus\AnnotationClassMapGenerator($classes))->getMap(),
                    )
                )
            )
        );
    }
}
