<?php

namespace Robertbaelde\Saucy\MessageStore;

use Robertbaelde\Saucy\MessageStore\ConcurrencyChecks\ConcurrencyCheck;

interface MessageStore
{
    public function appendToStream(Stream $stream, Messages $messages, ?ConcurrencyCheck $concurrencyCheck = null): void;

    public function getMessages(Stream $stream, ?Position $position= null, ?int $limit = null): Messages;
}
