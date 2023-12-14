<?php

namespace Robertbaelde\Saucy\Tests\stubs;

use Robertbaelde\Saucy\Attributes\Event;

#[Event('test_event')]
final readonly class TestEvent
{
    public function __construct()
    {
    }
}
