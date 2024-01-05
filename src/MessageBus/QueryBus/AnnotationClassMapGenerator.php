<?php

namespace Robertbaelde\Saucy\MessageBus\QueryBus;

use Robertbaelde\AttributeFinder\AttributeFinder;
use Robertbaelde\AttributeFinder\ClassAttribute;
use Robertbaelde\AttributeFinder\MethodAttribute;
use Robertbaelde\Saucy\Attributes\QueryHandler;

final class AnnotationClassMapGenerator
{
    /**
     * array<string, QueryHandlerLocation>
     */
    private array $map;

    /**
     * @param array<class-string> $classes
     */
    public function __construct(
        array $classes
    )
    {
        $attributes = AttributeFinder::inClasses($classes)->withName(QueryHandler::class)->findAll();
        $map = [];
        foreach ($attributes as $attribute){
            if($attribute instanceof ClassAttribute){
                throw new \Exception('Class ' . $attribute->class . ' is annotated with ' . QueryHandler::class . ' but class query handlers are not supported yet');
            }
            if($attribute instanceof MethodAttribute){
                $parameters = $attribute->method->getParameters();
                if(count($parameters) === 0){
                    throw new \Exception('Method ' . $attribute->method->getDeclaringClass() . '::' . $attribute->method->getName() . ' is annotated with ' . QueryHandler::class . ' but has no parameters');
                }
                $map[$parameters[0]->getType()->getName()] = new QueryHandlerLocation($attribute->method->getDeclaringClass()->getName(), $attribute->method->getName(),);
            }
        }

        $this->map = $map;
    }

    /**
     * @return array<string, QueryHandlerLocation>
     */
    public function getMap(): array
    {

        return $this->map;
    }
}
