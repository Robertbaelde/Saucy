<?php

namespace Robertbaelde\Saucy\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Projector
{
    public function __construct(
        public ?string $aggregateRootClass = null,
    )
    {
    }
}
