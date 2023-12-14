<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

interface CommandHandlerLocator
{
    public function getHandler(object $message): Handler;
}
