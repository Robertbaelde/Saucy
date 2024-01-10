<?php

namespace Robertbaelde\Saucy\EventSourcing\Projectors;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\EventConsumption\InflectHandlerMethodsFromType;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;

abstract class EloquentProjector implements MessageConsumer
{
    protected $idValue;

    protected function upsert(array $array): void
    {
        if(static::$model::find($this->idValue)){
            $this->update($array);
            return;
        }
        $this->create($array);
    }

    protected function create(array $array): void
    {
        static::$model::create(array_merge([
            $this->getKeyName() => $this->idValue,
        ], $array));
    }

    protected function update(array $data): void
    {
        $model = static::$model::findOrFail($this->idValue);
        $model->writable(array_keys($data))->update($data);
    }

    protected function increment(string $column, int $increment = 1): void
    {
        $model = static::$model::findOrFail($this->idValue);
        $model->writable([$column])->increment($column, $increment);
    }

    protected function getKeyName(): string
    {
        $instance = new static::$model;
        return $instance->getKeyName();
    }

    public function handle(Message $message): void
    {
        $this->idValue = $message->aggregateRootId()->toString();
        $methods = (new InflectHandlerMethodsFromType())->handleMethods($this, $message);
        foreach ($methods as $method) {
            if (method_exists($this, $method)) {
                $this->{$method}($message->payload(), $message->aggregateRootId(), $message);
            }
        }
    }
}
