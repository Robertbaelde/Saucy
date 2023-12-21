<?php

namespace Robertbaelde\Saucy\EventSourcing\EventSauce;

use EventSauce\EventSourcing\ClassNameInflector;

final readonly class SuffixClassNameInflector implements ClassNameInflector
{
    public function __construct(
        public ClassNameInflector $inner,
        public string $suffix,
    )
    {
    }

    public function classNameToType(string $className): string
    {
        return $this->inner->classNameToType($className) . $this->suffix;
    }

    public function typeToClassName(string $eventType): string
    {
        $typeWithSuffix = $this->inner->typeToClassName($eventType);
        $type = substr($typeWithSuffix, 0, -strlen($this->suffix));
    }

    public function instanceToType(object $instance): string
    {
        return $this->inner->instanceToType($instance) . $this->suffix;
    }
}
