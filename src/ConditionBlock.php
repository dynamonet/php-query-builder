<?php

namespace Dynamo\QueryBuilder;

/**
 * Represents a block of SQL conditions.
 * @author Eduardo Rodriguez Da Silva
 */
class ConditionBlock
{
    protected $conditions;
    protected $and;
    protected $bindings;

    public function __construct(array $conditions, bool $and = true)
    {
        $this->and = $and;
        $this->bindings = [];
        $this->conditions = new \Ds\Vector;
        $this->append($conditions);
    }

    public function toString(bool $prepare = true) : string
    {
        return '(' . implode(
            ( $this->and ? ' AND ' : ' OR ' ),
            $this->conditions->map(function($condition) use ($prepare){
                if(is_string($condition)){
                    return $condition;
                }

                return $condition->toString($prepare);
            })->toArray()
        ) . ')';
    }

    public function __toString() : string
    {
        return $this->toString(false);
    }

    public function isAnd() : bool
    {
        return $this->and;
    }

    /**
     * Adds new conditions to the existing ones
     */
    public function append(array $conditions)
    {
        foreach($conditions as $key => $value){
            $this->conditions->push($this->parseKeyValueCondition($key, $value));
        }
    }

    /**
     * @return string|SqlCondition
     */
    protected function parseKeyValueCondition($key, $value)
    {
        if(\is_numeric($key)){
            if(is_string($value)){
                return $value;
            } else if(is_array($value) && count($value) == 3){
                //caso [ columna, operador, valorDeseado ]
                return new SqlCondition(...$value);
            } else if($value instanceof ConditionBlock) {
                return $value;
            } else {
                throw new \Exception("Unexpected condition format");
            }
        } else if(is_string($key)) {

            $field = $key;

            // Determine operator
            if(
                \strpos($key, ' ') > 0 &&
                \preg_match(
                    '/\s+(!=|<>|>|>=|<|<=|IN|NOT IN|LIKE|NOT LIKE|BETWEEN)\s*$/i',
                    $key,
                    $matches
                )
            ){
                $field = \substr($key, 0, -\strlen($matches[0]));
                $operator = $matches[1];
            } else if($value === null){
                $operator = 'IS';
            } else if(\is_array($value)){
                $operator = 'IN';
            } else {
                $operator = '=';
            }

            return new SqlCondition($field, $operator, $value);
        } else {
            throw new \Exception("Not implemented 2!");
        }
    }
}