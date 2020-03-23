<?php

namespace Dynamo\QueryBuilder;

use stdClass;

/**
 * Class Builder.
 */
class SqlQueryBuilder implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $select;
    /**
     * @var array
     */
    private $insert;
    /**
     * @var array
     */
    private $update;
    /**
     * @var array
     */
    private $delete;
    /**
     * @var array
     */
    private $values;
    /**
     * @var array
     */
    private $set;
    /**
     * @var array
     */
    private $from;
    /**
     * @var array
     */
    private $where;// = [];
    /**
     * @var array
     */
    private $joins;
    /**
     * @var string
     */
    protected $entity;
    /**
     * @var string
     */
    private $group;
    /**
     * @var array
     */
    private $order;
    /**
     * @var int
     */
    private $limit;

    /**
     * @var PDO|xPDO
     */
    protected $pdo;

    /**
     * Gloabal xPDO that can be set statically, so all Queries use it
     * @var xPDO
     */
    protected static $global_pdo;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Query constructor.
     *
     * @param null|PDO|xPDO $pdo
     */
    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Sets a global PDO instance to be used by every new instance of this query builder
     */
    public static function setPdo($pdo)
    {
        self::$global_pdo = $pdo;
    }

    /**
     * Gets the pdo connection beign used by this instance
     */
    public function getPdo()
    {
        if($this->pdo !== null){
            return $this->pdo;
        } else if(self::$global_pdo != null){
            return self::$global_pdo;
        }

        return null;
    }

    /**
     * @param string $table
     * @param string $alias
     *
     * @return SqlQueryBuilder
     */
    public function from(string $table, ?string $alias = null): self
    {
        if ($alias) {
            $this->from[$table] = $alias;
        } else {
            $this->from[] = $table;
        }

        return $this;
    }

    /**
     * @param string|array $fields
     * @param bool $override Whether to override the existing select fields.
     *  Defaults to false, which appends new select field to the previously selected 
     * @return SqlQueryBuilder
     */
    public function select($fields, bool $override = false): self
    {
        if(is_string($fields)){
            $fields = explode(',', $fields);
        }

        if($override === true || $this->select == null){
            $this->select = $fields;
        } else {
            $this->select = array_merge($this->select, $fields);
        }

        return $this;
    }

    /**
     * @param string $table
     * @param array  $attributes
     *
     * @return SqlQueryBuilder
     */
    public function insert(string $table, ?array $attributes = null): self
    {
        $this->insert = $table;

        if ($attributes) {
            $this->values = [];
            $this->params = [];
            foreach($attributes as $field => $value){
                $this->values[$field] = '?';
                $this->params[] = $value;
            }
        }

        return $this;
    }

    /**
     * Creates an "INSERT - ON DUPLICATE KEY UPDATE" query
     */
    public function upsert(string $table, array $attributes, ?array $fieldsToUpdate = null): self
    {
        $this->insert = $this->update = $table;

        //Prepare INSERT first
        foreach($attributes as $field => $value){
            $this->values[$field] = '?';
            $this->params[] = $value;
        }

        //Prepare UPDATE
        foreach($attributes as $field => $value){
            if($fieldsToUpdate === null || in_array($field, $fieldsToUpdate)){
                $this->set[$field] = '?';
                $this->params[] = $value;
            }
        }

        return $this;
    }

    public function value(array $attributes): self
    {
        $this->values = $attributes;

        return $this;
    }

    /**
     * @param string $table
     * @param array  $attributes
     * @param int    $id
     *
     * @return SqlQueryBuilder
     */
    public function update(string $table, array $attributes, ?array $conditions = null): self
    {
        $this->update = $table;

        $this->params = [];
        foreach($attributes as $field => $value){
            $this->set[$field] = '?';
            $this->params[] = $value;
        }

        if ($conditions) {
            $this->where($conditions);
        }

        return $this;
    }

    public function set(array $attributes): self
    {
        $this->set = $attributes;

        return $this;
    }

    public function delete(string $table, ?int $id = null): self
    {
        $this->delete = $table;

        if ($id) {
            $this->where('id = :id');
            $this->params(['id' => $id]);
        }

        return $this;
    }

    /**
     * @param string|array $conditions
     *
     * @return SqlQueryBuilder
     */
    public function where($conditions, $operator = 'AND'): self
    {
        if($conditions !== null){
            if($this->where == null){
                $this->where = new ConditionBlock($conditions, $operator);
            } else {
                $this->where->append($conditions);
            }
        }

        return $this;
    }

    public function andWhere($conditions, bool $and = true) : self
    {
        if($this->where == null){
            throw new \Exception("'where' is not set. Use 'where(...)' before using 'andWhere(...)'");
        }

        if($and === true && $this->where->isAnd()){
            $this->where->append($conditions);
        } else {
            $this->where = new ConditionBlock([
                $this->where,
                new ConditionBlock($conditions, $and)
            ]);
        }

        return $this;
    }

    public function orWhere($conditions) : self
    {
        throw new \Exception("Not implemented");
    }

    /**
     * @param string $table
     * @param string|array $condition
     * @param bool $and
     *
     * @return SqlQueryBuilder
     */
    public function join(
        string $table,
        $condition,
        bool $and = true,
        string $type = 'inner'
    ): self
    {
        $this->joins[$type][] = [
            $table,
            new ConditionBlock($condition, $and)
        ];

        return $this;
    }

    public function leftJoin(
        string $table,
        $condition,
        bool $and = true
    ): self
    {
        return $this->join(
            $table,
            $condition,
            $and,
            'left'
        );
    }

    /**
     * @throws \Exception
     *
     * @return int
     */
    public function count(): int
    {
        $query = clone $this;
        $table = current($this->from);

        return $query->select("COUNT({$table}.id)")->execute()->fetchColumn();
    }

    /**
     * @param string      $column
     * @param null|string $direction
     *
     * @return SqlQueryBuilder
     */
    public function orderBy(string $column, ?string $direction = 'ASC'): self
    {
        $this->order[$column] = $direction;

        return $this;
    }

    /**
     * @param string $column
     *
     * @return SqlQueryBuilder
     */
    public function groupBy(string $column): self
    {
        $this->group = $column;

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return SqlQueryBuilder
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = "$offset, $limit";

        return $this;
    }

    /**
     * @param string $entity
     *
     * @return SqlQueryBuilder
     */
    public function into($entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function fetchOne()
    {
        $record = $this->execute()->fetch(\PDO::FETCH_ASSOC);

        if (false === $record) {
            return false;
        }

        return $this->getHydratedEntity($record);
    }

    public function fetchAll()
    {
        $rows = $this->execute()->fetchAll(\PDO::FETCH_ASSOC);

        if (false === $rows) {
            return false;
        }

        $result = new \Ds\Vector();
        
        foreach($rows as $row){ 
            $result->push($this->getHydratedEntity($row));
        }
        
        return $result;
    }

    protected function getHydratedEntity($data)
    {
        $entityClass = 'stdClass';

        if($this->entity){
            if(is_callable($this->entity)){
                $entityClass = ($this->entity)($data);
            } else {
                $entityClass = $this->entity;
            }
        }

        $entity = new $entityClass();

        foreach($data as $field => $value){
            $entity->$field = $value;
        }

        return $entity;
    }

    /**
     * @throws \Exception
     */
    public function fetchOrFail()
    {
        $record = $this->fetchOne();

        if (false === $record) {
            throw new \Exception('No query results for model');
        }

        return $record;
    }

    /**
     * @param array $params
     * @param bool  $merge
     *
     * @return SqlQueryBuilder
     */
    public function params(array $params, bool $merge = true): self
    {
        if ($merge) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params = $params;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getSql(false);
    }

    /**
     * @return string
     */
    public function getSql(bool $prepared = false)
    {
        $parts = ['SELECT'];

        if ($this->select && count($this->select) > 0) {
            $parts[] = implode(', ', $this->select);
        } else {
            $parts[] = '*';
        }

        if ($this->insert) {
            $parts = ['INSERT INTO ' . $this->insert];
            if ($this->values) {
                $parts[] = '(' . implode(', ', array_keys($this->values)) . ')';
                $parts[] = 'VALUES';
                $parts[] = '(' . implode(', ', array_values($this->values)) . ')';
            }
            if ($this->update && $this->set) {
                $parts[] = ' ON DUPLICATE KEY UPDATE ';
                $sets = [];
                foreach ($this->set as $key => $value) {
                    $sets[] = "$key = $value";
                }
                $parts[] = implode(', ', $sets);
            }
        } else if ($this->update) {
            $parts = ['UPDATE ' . $this->update . ' SET'];

            if ($this->set) {
                $sets = [];
    
                foreach ($this->set as $key => $value) {
                    $sets[] = "$key = $value";
                }
                $parts[] = implode(', ', $sets);
            }
        }

        if ($this->delete) {
            $parts = ['DELETE FROM ' . $this->delete];
        }

        if ($this->from) {
            $parts[] = 'FROM';
            $parts[] = $this->buildFrom();
        }

        if (!empty($this->joins)) {
            foreach ($this->joins as $type => $joins) {
                foreach ($joins as [$table, $condition]) {
                    $parts[] = mb_strtoupper($type) . " JOIN $table ON $condition";
                }
            }
        }

        //if (!empty($this->where)) {
        if ($this->where != null) {
            $parts[] = 'WHERE';
            $parts[] = $this->where->toString($prepared); //'(' . implode(') AND (', $this->where) . ')';
        }

        if ($this->order) {
            foreach ($this->order as $key => $value) {
                $parts[] = "ORDER BY $key $value";
            }
        }

        if ($this->group) {
            $parts[] = 'GROUP BY ' . $this->group;
        }

        if ($this->limit) {
            $parts[] = 'LIMIT ' . $this->limit;
        }

        return implode(' ', $parts);
    }

    private function buildFrom(): string
    {
        $from = [];

        foreach ($this->from as $key => $value) {
            if (\is_string($key)) {
                $from[] = "$key as $value";
            } else {
                $from[] = $value;
            }
        }

        return implode(', ', $from);
    }

    public function execute()
    {
        $sql = $this->__toString();

        if (!empty($this->params)) {
            $statement = $this->getPdo()->prepare($sql);

            if (!$statement->execute($this->params)) {
                throw new \Exception("Sql Error by execute query: {$sql}");
            }

            return $statement;
        }

        return $this->getPdo()->query($sql);
    }

    /**
     * @return \Traversable
     */
    public function getIterator()
    {
        return $this->fetchAll();
    }
}
