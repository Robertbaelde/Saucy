<?php

namespace Workbench\App\Events;

use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Robertbaelde\Saucy\Attributes\Event;

#[Event('bank_account_credited')]
final readonly class BankAccountCredited implements SerializablePayload
{
    public function __construct(
        public int $amount
    ) {
    }

    public function toPayload(): array
    {
        return [
            'amount' => $this->amount,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new BankAccountCredited(
            $payload['amount'],
        );
    }
}
