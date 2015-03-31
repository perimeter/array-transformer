<?php

namespace Perimeter\ArrayTransformer\Tests;

use Perimeter\ArrayTransformer\ArrayTransformer;

class ArrayTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the renaming of keys
     *
     * @dataProvider provideRename
     */
    public function testRename($data, $old, $new, $expected, $result = true)
    {
        $transformer = new ArrayTransformer($data);
        $this->assertEquals($transformer->rename($old, $new), $result);
        $this->assertEquals($expected, $data);
    }

    public function provideRename()
    {
        return [
            [
                ['A'=>true],
                'A',
                'B',
                ['B'=>true]
            ],
            [
                ['A'=>['B'=>true]],
                'A/B',
                'A/C',
                ['A'=>['C'=>true]],
            ],
            [
                ['A'=>[['B'=>true], ['B'=>true],[ 'B'=>true]]],
                'A/?/B',
                'A/?/C',
                ['A'=>[['C'=>true], ['C'=>true],[ 'C'=>true]]],
            ],
            [
                [['A'=>true, 'B'=>true], ['A'=>true, 'B'=>true], ['A'=>true, 'B'=>true]],
                '?/B',
                '?/C',
                [['A'=>true, 'C'=>true], ['A'=>true, 'C'=>true], ['A'=>true, 'C'=>true]],
            ],
            // paths of different lengths
            [
                ['A'=>['B'=>true,'C'=>true]],
                'A/B',
                'B',
                ['A'=>['C'=>true],'B'=>true],
            ],
            [
                ['A'=>['C'=>true],'B'=>true],
                'B',
                'A/B',
                ['A'=>['B'=>true,'C'=>true]],
            ],
            // wildcards
            [
                ['A'=>[['C'=>true], ['D'=>true]],'B'=>true],
                'B',
                'A/?/B',
                ['A'=>[['C'=>true,'B'=>true],['D'=>true,'B'=>true]]],
            ],
            [
                ['A'=>[['C'=>true,'B'=>true],['D'=>true,'B'=>true]]],
                'A/?/B',
                'B',
                ['A'=>[['C'=>true], ['D'=>true]],'B'=>[true,true]],
            ],
            // array indexing
            [
                ['A'=>[['C'=>true], ['D'=>true]],'B'=>true],
                'B',
                'A/1/B',
                ['A'=>[['C'=>true],['D'=>true,'B'=>true]]],
            ],
            [
                ['A'=>[['C'=>true],['D'=>true,'B'=>true]]],
                'A/1/B',
                'B',
                ['A'=>[['C'=>true], ['D'=>true]],'B'=>true],
            ],
            // paths don't exist
            [
                ['A'=>true],
                'X',
                'B',
                ['A'=>true],
                false
            ],
            [
                ['A'=>['B'=>true]],
                'X/B',
                'X/C',
                ['A'=>['B'=>true]],
                false
            ],
        ];
    }

    /**
     * Tests providing selectors
     *
     * @dataProvider provideSelector
     */
    public function testSelector($data, $path, $expected)
    {
        $transformer = new ArrayTransformer($data);
        $transformer->remove($path);
        $this->assertEquals($expected, $data);
    }

    public function provideSelector()
    {
        return [
            [
                ['A'=>['B'=>1],'C'=>true],
                'A:has(B=1)',
                ['C'=>true]
            ],
            [
                ['A'=>['B'=>1],'C'=>true],
                'A:has(B=2)',
                ['A'=>['B'=>1],'C'=>true],
            ],
            [
                ['A'=>['B'=>'foo'],'C'=>true],
                'A:has(B="foo")',
                ['C'=>true],
            ],
            [
                ['A'=>[['B'=>0],['B'=>1]]],
                'A/?:has(B=1)',
                ['A'=>[['B'=>0]]],
            ],
            [
                ['A'=>['B'=>1],'C'=>1],
                'C:equals(1)',
                ['A'=>['B'=>1]],
            ],
            [
                ['A'=>['B'=>1],'C'=>true],
                'C:equals(true)',
                ['A'=>['B'=>1]],
            ],
            [
                ['A'=>['B'=>1],'C'=>'foo'],
                'C:equals("foo")',
                ['A'=>['B'=>1]],
            ],
            [
                ['A'=>['B'=>1],'C'=>2],
                'C:equals(1)',
                ['A'=>['B'=>1],'C'=>2],
            ],
            [
                ['A'=>['B'=>1],'C'=>true],
                'C:equals(1)',
                ['A'=>['B'=>1],'C'=>true],
            ],
            [
                ['A'=>['B'=>1],'C'=>true],
                'C:equals(false)',
                ['A'=>['B'=>1],'C'=>true],
            ],
        ];
    }

    /**
     * Tests the replacement of values
     *
     * @dataProvider provideReplace
     */
    public function testReplace($data, $path, $map, $expected, $result = true)
    {
        $transformer = new ArrayTransformer($data);
        $this->assertEquals($transformer->replace($path, $map), $result);
        $this->assertEquals($expected, $data);
    }

    public function provideReplace()
    {
        return [
            [
                ['A'=>1],
                'A',
                [1=>2],
                ['A'=>2]
            ],
            [
                [['A'=>1],['A'=>3]],
                '?/A',
                [1=>2,3=>4],
                [['A'=>2],['A'=>4]]
            ],
            // path doesn't exist
            [
                ['A'=>1],
                'X',
                [1=>2],
                ['A'=>1],
                false
            ],
            [
                [['A'=>1],['A'=>3]],
                '?/X',
                [1=>2,3=>4],
                [['A'=>1],['A'=>3]],
                false
            ],
            // partial replace (uses str_replace)
            [
                ['A'=>'abca'],
                'A',
                ['a'=>'x'],
                ['A'=>'xbcx']
            ],
            [
                [['A'=>'test'],['A'=>'retester']],
                '?/A',
                ['test'=>'done'],
                [['A'=>'done'],['A'=>'redoneer']]
            ],
        ];
    }

    /**
     * Tests translating
     *
     * @dataProvider provideTranslate
     */
    public function testTranslate($data, $path, $map, $expected, $result = true)
    {
        $transformer = new ArrayTransformer($data);
        $this->assertEquals($transformer->translate($path, $map), $result);
        $this->assertEquals($expected, $data);
    }

    public function provideTranslate()
    {
        return [
            [
                ['A'=>1],
                'A',
                [1=>2],
                ['A'=>2]
            ],
            [
                [['A'=>1],['A'=>3]],
                '?/A',
                [1=>2,3=>4],
                [['A'=>2],['A'=>4]]
            ],
            [
                [['A'=>1],['A'=>3]],
                '?/X',
                [1=>2,3=>4],
                [['A'=>1],['A'=>3]],
                false
            ],
        ];
    }

    /**
     * Tests applying functions to all values at a path
     *
     * @dataProvider provideReorder
     */
    public function testReorder($data, $path, $position, $otherkey, $expected, $result = true)
    {
        $transformer = new ArrayTransformer($data);
        $this->assertEquals($transformer->reorder($path, $position, $otherkey), $result);
        $this->assertEquals(array_keys($expected), array_keys($data));
        $this->assertEquals(array_values($expected), array_values($data));
    }

    public function provideReorder()
    {
        return [
            [
                ['A'=>true,'B'=>false],
                'A',
                'after',
                'B',
                ['B'=>false,'A'=>true],
            ],
            [
                ['A'=>true,'B'=>false],
                'B',
                'before',
                'A',
                ['B'=>false,'A'=>true],
            ],
            [
                ['A'=>true,'B'=>false,'C'=>true],
                'C',
                'first',
                null,
                ['C'=>true,'A'=>true,'B'=>false],
            ],
            [
                ['A'=>true,'B'=>false,'C'=>true],
                'A',
                'last',
                null,
                ['B'=>false,'C'=>true,'A'=>true],
            ],
        ];
    }

    /**
     * Tests applying functions to all values at a path
     *
     * @dataProvider provideModify
     */
    public function testModify($data, $path, $callable, $expected)
    {
        $transformer = new ArrayTransformer($data);
        $transformer->modify($path, $callable);
        $this->assertEquals($expected, $data);
    }

    public function provideModify()
    {
        return [
            [
                ['A'=>'tEst','B'=>'LOWERCASE'],
                '?',
                'strtolower',
                ['A'=>'test','B'=>'lowercase'],
            ],
            [
                [['A'=>'tEst','B'=>'LOWERCASE']],
                '?/B',
                'strtolower',
                [['A'=>'tEst','B'=>'lowercase']],
            ],
            [
                ['A'=>'tEst','B'=>'LOWERCASE'],
                'B',
                'strtolower',
                ['A'=>'tEst','B'=>'lowercase'],
            ],
            [
                ['A'=>'boat','B'=>['car','track']],
                'B/?',
                function ($var) { return 'race'.$var; },
                ['A'=>'boat','B'=>['racecar', 'racetrack']],
            ],
            [
                ['A'=>'boat','B'=> 'car'],
                'C',
                function ($var) { return 'plane'; },
                ['A'=>'boat','B'=> 'car'],
            ],
        ];
    }

    /**
     * Tests the removal of values
     *
     * @dataProvider provideRemove
     */
    public function testRemove($data, $path, $expected, $result = true)
    {
        $transformer = new ArrayTransformer($data);
        $this->assertEquals($transformer->remove($path), $result);
        $this->assertEquals($expected, $data);
    }

    public function provideRemove()
    {
        return [
            [
                ['A'=>1,'B'=>2],
                'A',
                ['B'=>2]
            ],
            [
                [['A'=>1,'B'=>2],['A'=>3,'B'=>4]],
                '?/A',
                [['B'=>2],['B'=>4]]
            ],
            // path doesn't exist
            [
                ['A'=>1,'B'=>2],
                'X',
                ['A'=>1,'B'=>2],
                false
            ],
            [
                [['A'=>1,'B'=>2],['A'=>3,'B'=>4]],
                '?/X',
                [['A'=>1,'B'=>2],['A'=>3,'B'=>4]],
                false
            ],
        ];
    }

    /**
     * Tests for good and bad paths in data
     *
     * @dataProvider provideCheckPath
     */
    public function testCheckPath($data, $goodPath, $badPath)
    {
        $transformer = new ArrayTransformer($data);
        $this->assertTrue($transformer->checkPath($goodPath));
        $this->assertFalse($transformer->checkPath($badPath));
    }

    public function provideCheckPath()
    {
        return [
            [
                ['A'=>1],
                'A',
                'X',
            ],
            [
                ['A'=>['B'=>1]],
                'A/B',
                'X/B'
            ],
        ];
    }
}
