<?php

namespace Robertbaelde\Saucy\ClassNameMapper;

use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use ReflectionClass;
use Robertbaelde\Saucy\Attributes\Event;

final readonly class AttributeClassNameInflector implements ClassNameInflector
{
    private ExplicitlyMappedClassNameInflector $inner;

    private function __construct($classMap)
    {
        $this->inner = new ExplicitlyMappedClassNameInflector($classMap);
    }

    public static function create(array $classes): self
    {
        $classMap = [];
        foreach ($classes as $class) {
            $name = self::getNameByAttribute($class);
            if($name !== null){
                $classMap[$class] = $name;
            }
        }
        return new self($classMap);
    }

    public function classNameToType(string $className): string
    {
        return $this->inner->classNameToType($className);
    }

    public function typeToClassName(string $eventType): string
    {
        return $this->inner->typeToClassName($eventType);
    }

    public function instanceToType(object $instance): string
    {
        return $this->inner->instanceToType($instance);
    }

    private static function getNameByAttribute(object|string $instance): ?string
    {
        $reflection = new ReflectionClass($instance);
        $attributes = $reflection->getAttributes(Event::class);
        if(count($attributes) === 0) {
            return null;
        }

        $attribute = $attributes[0]->newInstance();
        if(!$attribute instanceof Event) {
            return null;
        }
        return $attribute->name;
    }
}
