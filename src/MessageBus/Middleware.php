<?php

namespace Robertbaelde\Saucy\MessageBus;

interface Middleware
{
    public function run(object $message, callable $next);
}
