<?php

namespace Dynamo\QueryBuilder;

/**
 * PDO wrapper that performs automatic reconnections
 */
class xPDO
{
    protected $pdo_driver, $host, $port, $default_schema, $username, $password, $pdo, $driver_options;
    
    public function __construct(
        string $pdo_driver,
        string $host,
        int $port,
        string $default_schema,
        string $username = "",
        string $password = "",
        $driver_options = []
    ){
        $this->pdo_driver = $pdo_driver;
        $this->host = $host;
        $this->port = $port;
        $this->default_schema = $default_schema;
        $this->username = $username;
        $this->password = $password;
        $this->driver_options = $driver_options;
    }

    public function __call($name, array $arguments)
    {
        $result = null;

        try {
            $result = call_user_func_array(
                [ $this->connection(), $name ],
                $arguments
            );
        } catch(\PDOException $e) {
            if(stripos($e->getMessage(), 'server has gone away') !== false) {
                $result = call_user_func_array(
                    [ $this->reconnect(), $name ],
                    $arguments
                );
            } else {
                throw $e;
            }
        }

        return $result;
    }

    protected function connection()
    {
        return $this->pdo instanceof \PDO ? $this->pdo : $this->connect();
    }

    public function connect()
    {
        $this->pdo = new \PDO(
            sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=utf8',
                $this->pdo_driver,
                $this->host,
                $this->port,
                $this->default_schema
            ),
            $this->username,
            $this->password,
            (array) $this->driver_options
        );

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $this->pdo;
    }

    public function reconnect()
    {
        $this->pdo = null;
        return $this->connect();
    }
}