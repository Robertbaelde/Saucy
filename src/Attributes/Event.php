<?php

namespace Robertbaelde\Saucy\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Event
{
    public function __construct(
        public string $name,
    )
    {
    }
}
