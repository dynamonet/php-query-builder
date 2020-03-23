# Sql Query Builder for PHP 7.1 (and later)
Fluent and flexible SQL-query-builder, merging in the best ideas from all query-builders out there.

## Quick Overview
```php
use Dynamo\QueryBuilder\SqlQueryBuilder as Query;

$users = (new Query)
  ->select('*') // This is the default select
  ->from('users u')
  ->leftJoin('posts p',[
    'p.user_id' => 'u.id',
    'p.trashed' => null
  ]) // translates to: LEFT JOIN posts p ON p.user_id = u.id AND p.trashed IS NULL
  ->where([
    'role' => 'ADMIN', // translates to "role = ?", where "?" will be securely replaced by the PDO layer
    "age > $minAge", // insecure! $minAge will not be prepared! However, we allow this form for convenience
    'age >' => $minAge, // better, and prepareable
    [ 'age', '<=', $maxAge ], // field-operator-value array if you prefer
    'age BETWEEN' => [ $minAge, $maxAge ], // even better
  ], false) // false "OR's" all the previous conditions. Default is true, which will "AND" all the conditions 
  ->fetchAll(); // Fetches all the results
```

## Working with PDO connections

Create a new PDO instance, and pass it to the SqlQueryBuilder constructor:
```php
use Dynamo\QueryBuilder\SqlQueryBuilder as Query;
....
$pdo = new \PDO('mysql:dbname=mydb;host=localhost;charset=utf8', 'myuser', 'mypass');
$query = (new Query($pdo))
```
or better yet, set the PDO globally:
```php
Query::setPdo($pdo);
....
$query = (new Query()) // this will use the static PDO instance.
```

### Installing

```php
composer require dynamonet/query-builder
```

