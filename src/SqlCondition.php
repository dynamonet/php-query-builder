<?php

namespace Dynamo\QueryBuilder;

class SqlCondition
{
    protected $field;
    protected $operator;
    protected $value;
    protected $bindings;

    public function __construct($field, $operator, $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function toString($prepare = false)
    {
        return \sprintf(
            "%s %s %s",
            $this->field,
            $this->operator,
            $this->formatValue($prepare)
        );
    }

    protected function formatValue($prepare = false) : string
    {
        if($this->value === null){
            return 'NULL';
        }
        
        if($prepare === true){

            if(is_array($this->value)){
                if(strcasecmp($this->operator, 'BETWEEN') === 0){
                    return '? AND ?';
                }

                return '(' . str_repeat ('?, ', count($this->value) - 1) . '?)';
            } else {
                return '?';
            }
        }

        // not-prepared
        if(is_array($this->value)){

            if(strcasecmp($this->operator, 'BETWEEN') === 0){
                return $this->escape($this->value[0]) . ' AND ' . $this->escape($this->value[1]);
            }

            return '(' . implode(
                ', ',
                array_map(
                    function($item){
                        if(is_numeric($item)){
                            return $item;
                        }
                        return $this->escape($item);
                    },
                    $this->value
                )
            ) . ')';
        }

        if(\is_numeric($this->value)){
            return $this->value;
        }

        return $this->escape($this->value);
    }

    protected function escape($value)
    {
        $search = ["\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a"];
        $replace = ["\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z"];

        return "'" . str_replace($search, $replace, $value) . "'";
    }
}