<?php

namespace Robertbaelde\Saucy\ClassNameMapper;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\ExplicitlyMappedClassNameInflector;
use ReflectionClass;
use Robertbaelde\AttributeFinder\AttributeFinder;
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
        foreach(AttributeFinder::inClasses($classes)->withName(Event::class)->findClassAttributes() as $classAttribute){
            $attribute = $classAttribute->attribute;
            // should not be required, but makes PhpStan happy
            if(!$attribute instanceof Event){
                continue;
            }
            $classMap[$classAttribute->class] = $attribute->name;
        }

        foreach ($classes as $class){
            $reflectionClass = new ReflectionClass($class);
            $name = self::getNameByAggregateRootIdConvention($reflectionClass);
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

    private static function getNameByAggregateRootIdConvention(ReflectionClass $reflectionClass): ?string
    {

        if($reflectionClass->implementsInterface(AggregateRootId::class)){
            return $reflectionClass->getShortName();
        }

        return null;
    }
}
