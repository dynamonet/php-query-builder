<?php

namespace Dynamo\QueryBuilder;

use Symfony\Contracts\EventDispatcher\Event;

class SqlQueryEvent extends Event
{
    protected $sql;

    function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    public function getSql()
    {
        return $this->sql;
    }
}