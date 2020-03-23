<?php

use Dynamo\QueryBuilder\ConditionBlock;
use PHPUnit\Framework\TestCase;

use Dynamo\QueryBuilder\SqlQueryBuilder as Query;

class ConditionBlockTest extends TestCase
{
    /**
     * @dataProvider keyValueProvider
     */
    public function testKeyValueCombinations($key, $value, $expected)
    {
        $sql = (string) (new ConditionBlock([ $key => $value ]));

        $this->assertEquals("($expected)", $sql);
    }

    public function keyValueProvider()
    {
        return [
            [ 'a', 'b', "a = 'b'" ],
            [ 'a', null, 'a IS NULL'],
            [ 'a', [1,2,3], 'a IN (1, 2, 3)'],
            [ 'a NOT IN', [1,2,3], 'a NOT IN (1, 2, 3)'],
            [ 'a !=', 'b', "a != 'b'"],
            [ 'a LIKE', '%sarasa%', "a LIKE '%sarasa%'"]
        ];
    }
}
