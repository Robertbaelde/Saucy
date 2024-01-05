<?php

namespace Robertbaelde\Saucy\MessageBus\QueryBus;

use Robertbaelde\Saucy\MessageBus\MessageBus;
final readonly class QueryBus extends MessageBus
{

    /**
     * @template T
     * @param Query<T> $query
     * @return T
     */
    public function query(Query $query)
    {
        return $this->handle($query);
    }
}
