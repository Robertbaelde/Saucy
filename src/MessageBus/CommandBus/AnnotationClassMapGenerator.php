<?php

namespace Robertbaelde\Saucy\MessageBus\CommandBus;

use ReflectionClass;
use Robertbaelde\Saucy\Attributes\CommandHandler;

final class AnnotationClassMapGenerator
{
    /**
     * array<string, Handler>
     */
    private array $map;

    /**
     * @param array<class-string> $classes
     */
    public function __construct(
        array $classes
    )
    {
        $map = [];
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw new \Exception('Class ' . $class . ' does not exist');
            }

            $reflection = new ReflectionClass($class);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method){
                $attributes = $method->getAttributes(CommandHandler::class);

                if(count($attributes) === 0) {
                    continue;
                }

                $attribute = $attributes[0]->newInstance();

                if($attribute->handlingCommand !== null){
                    if(array_key_exists($attribute->handlingCommand, $map)){
                        throw new \Exception('Command ' . $attribute->handlingCommand . ' is already handled by ' . $map[$attribute->handlingCommand]->class . '::' . $map[$attribute->handlingCommand]->method);
                    }
                    $map[$attribute->handlingCommand] = new Handler($class, $method->getName());
                    continue;
                }

                // get first parameter of method, this is the command it is handling
                $handlingCommand = $method->getParameters()[0]->getType()->getName();
                if(array_key_exists($handlingCommand, $map)){
                    throw new \Exception('Command ' . $handlingCommand . ' is already handled by ' . $map[$handlingCommand]->containerIdentifier . '::' . $map[$handlingCommand]->methodName);
                }

                $map[$handlingCommand] = new Handler($class, $method->getName());
            }

            $this->map = $map;
        }
    }

    /**
     * @return array<string, Handler>
     */
    public function getMap(): array
    {

        return $this->map;
    }
}
