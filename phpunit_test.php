<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

class BookKingTest extends TestCase
{
  
    public function getArrays(){
        $readjson = file_get_contents('data.json');

        $data = json_decode($readjson, true);

        $idArray = array();
        $descArray = array();

        foreach($data as $entry){
            $desc = $entry['DESCRIPTION'];
            $id = $entry['ID'];
            array_push($idArray, $id);
            array_push($descArray, $desc);
        }
        
        return $descArray;
    }
    
    public function testDescription(){
        $descArray = $this->getArrays();
        $this->assertSame("Testing", $descArray[0]);
    }
  
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
    
}