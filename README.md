# Sql Query Builder for PHP 7.1 (and later)
Fluent and flexible SQL-query-builder, merging in the best ideas from all query-builders out there.

## Quick Overview
```php
use Dynamo\QueryBuilder\SqlQueryBuilder as Query;

$users = (new Query)
  ->select('*') // This is the default select
  ->from('users u')
  ->leftJoin('posts p',[
    'p.user_id = u.id',
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

#### A warning on JOINs with array conditions
Notice that in the previous example, inside the join method, the first condition is 'p.user_id = u.id'  instead of the more beatiful form 'p.user_id' => 'u.id', this is because the latter would have be translated to "p.user_id = 'u.id'", matching the posts where user_id is equal to the literal string "u.id" (not what we want). 

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

## Install

```php
composer require dynamonet/query-builder
```

