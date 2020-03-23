<?php

use PHPUnit\Framework\TestCase;

use Dynamo\QueryBuilder\SqlQueryBuilder as Query;

class SqlQueryBuilderTest extends TestCase
{
    /**
     * @dataProvider joinCaseProvider
     */
    public function testJoin($joinMethod, $condition, $expected)
    {
        $query = (new Query)
            ->select('*')
            ->from('user u')
            ->$joinMethod('posts p', $condition);

        $result = $query->getSql(true);

        $expected_query = 'SELECT * FROM user u ' . $expected;

        $this->assertEquals($expected_query, $result);
    }

    public function testSqlQueryBuilder()
    {
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

        //echo $sql . "\n";

        $this->assertIsString($sql);
    }

    public function joinCaseProvider()
    {
        return [
            [
                'join',
                'p.user_id = u.id',
                "INNER JOIN posts p ON (p.user_id = u.id)"
            ],
            [
                'leftJoin',
                [
                    'p.user_id = u.id',
                    'p.trashed' => null
                ],
                "LEFT JOIN posts p ON (p.user_id = u.id AND p.trashed IS NULL)"
            ],
        ];
    }
}
