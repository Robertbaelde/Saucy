<?php

namespace Robertbaelde\Saucy\EventSourcing\EventStore;


final readonly class Headers
{
    public function __construct(
        public array $headers,
    )
    {
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public static function fromArray(array $headers): self
    {
       return new self($headers);
    }

    public function with(EventStoreHeader | string $header, string|int|array $value): self
    {
        if($header instanceof EventStoreHeader) {
            $header = $header->value;
        }

        return new self(array_merge($this->headers, [$header => $value]));
    }

    public function get(EventStoreHeader | string $header): string|int|array|null
    {
        if($header instanceof EventStoreHeader) {
            $header = $header->value;
        }
        return $this->headers[$header] ?? null;
    }
}
