<?php

namespace Robertbaelde\Saucy\Tests\Workbench;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Robertbaelde\Saucy\SaucyServiceProvider;

abstract class WithDatabaseTestCase extends TestCase
{
    use RefreshDatabase, WithWorkbench;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function getPackageProviders($app)
    {
        return [SaucyServiceProvider::class];
    }


}
