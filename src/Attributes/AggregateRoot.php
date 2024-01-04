<?php

namespace Robertbaelde\Saucy\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AggregateRoot
{
    public function __construct(
        public string $aggregateRootIdClass,
        public ?string $name = null,
    )
    {
    }
}
