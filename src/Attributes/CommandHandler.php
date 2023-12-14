<?php

namespace Robertbaelde\Saucy\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CommandHandler
{
    public function __construct(
        public ?string $handlingCommand = null,
    )
    {
    }
}
