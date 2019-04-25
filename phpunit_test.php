<?php
use PHPUnit\Framework\TestCase;

class StackTest extends TestCase
{
    public function testRecordCount(){
        $stack = [];
        $this->assertSame(0, count($stack));

        array_push($stack, 'foo');
        $this->assertSame('foo', $stack[count($stack)-1]);
        $this->assertSame(1, count($stack));

        $this->assertSame('foo', array_pop($stack));
        $this->assertSame(0, count($stack));
    }
    
    public function testPushAndPop(){
        $arr = [];

        array_push($arr, 'Bob');
        array_push($arr, 'Andy');
        $this->assertSame('Bob', $arr[0]);
    }
    
    public function testDb($table, $field, $value, $expect) {
        global $DB;

        $act = $DB->count_records($table, array($field => $value));
        $this->assertEquals($expect, $act, "Checking whether table $table has $expect records with $field equal to $value");
    }
    
}