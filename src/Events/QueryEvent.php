<?php

namespace Dynamo\QueryBuilder\Events;

use Dynamo\Events\EventData;

class QueryEvent extends EventData
{
    protected $sql;

    function __construct($name, $sql)
    {
        parent::__construct($name);
        $this->sql = $sql;
    }

    public function getSql()
    {
        return $this->sql;
    }
}