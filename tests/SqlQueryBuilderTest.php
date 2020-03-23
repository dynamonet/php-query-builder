<?php

use PHPUnit\Framework\TestCase;

use Dynamo\QueryBuilder\SqlQueryBuilder as Query;

class SqlQueryBuilderTest extends TestCase
{
    public function testSqlQueryBuilder()
    {
        $stack = [];
        $query = (new Query)
            ->select('a, b, c, d')
            ->select([ 'e', 'f', 'g', 'h'])
            ->from('myTable', 't')
            ->where([
                'a = 1',
                'b' => 2,
                'c' => [ 3, 4, 5],
                'c <>' => 6
            ])
            ->andWhere([
                'd LIKE' => '%sarasa%',
                'e NOT LIKE' => '%milanesa%',
                'f' => null
            ], false);

        $sql = $query->getSql(true);

        echo $sql . "\n";

        $this->assertIsString($sql);
    }
}
